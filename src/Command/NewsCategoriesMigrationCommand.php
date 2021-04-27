<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Command;

use Contao\CoreBundle\Command\AbstractLockedCommand;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use HeimrichHannot\CategoriesBundle\Model\CategoryAssociationModel;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NewsCategoriesMigrationCommand extends AbstractLockedCommand
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var array
     */
    protected $categories;

    /**
     * @var array
     */
    protected $categoryAssociations;

    /**
     * @var array
     */
    protected $categoryIdMapping;

    /**
     * tl_news category field name.
     *
     * @var string
     */
    protected $field;

    /**
     * @var bool
     */
    protected $dryRun = false;
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        parent::__construct();
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('huh:categories:migrate-news-categories')->setDescription(
            'Migration of database entries from contao-news_categories module to contao-categories-bundle.'
        );

        $this->addArgument('field', InputArgument::OPTIONAL, 'What is the name of the category field in tl_news (default: categories)?', 'categories');
        $this->addOption('legacy-categories', 'i', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Only import categories with this ids and their children.');
        $this->addOption('exclude-legacy-categories', 'x', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Skip these categories and their children.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Performs a run without writing to datebase and copy templates.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        $io->title('Start contao-news_categories to contao-categorie-bundle migration');

        if ($input->hasOption('dry-run') && $input->getOption('dry-run')) {
            $this->dryRun = true;
            $io->note('Dry run enabled, no data will be changed.');
            $io->newLine();
        }

        $this->field = $input->getArgument('field');
        $this->input = $input;
        $this->output = $output;

        if ($success = $this->migrateCategories($io)) {
            $this->migrateAssociations($io);
        }

        if ($success) {
            $io->success('Finished news categories migration!');
        }

        return 0;
    }

    /**
     * Migrate tl_news_category to tl_category.
     */
    protected function migrateCategories(SymfonyStyle $io): bool
    {
        $io->section('Start news category migration.');

        $db = Database::getInstance();

        if ($this->input->hasOption('legacy-categories')) {
            $parentCategoryIds = $this->input->getOption('legacy-categories');
        }

        $qb = $this->getQueryBuilder()->select('c.*')->from('tl_news_category', 'c');
        $newsCategoriesIds = [];

        if (!empty($parentCategoryIds)) {
            foreach ($parentCategoryIds as $category) {
                // Check if a category with given id exists, abort otherwise
                $result = $db->prepare('SELECT id FROM tl_news_category WHERE id=?')->execute($category);

                if ($result->count() < 1) {
                    $io->error('A legacy category with id '.$category.' for import could not be found. Stopping migration to prevent data errors.');

                    return false;
                }
            }

            $childCategories = $db->getChildRecords($parentCategoryIds, 'tl_news_category');
            $newsCategoriesIds = array_merge($parentCategoryIds, $childCategories);
        }

        if ($this->input->hasOption('exclude-legacy-categories')) {
            $parentExcludeCategories = $this->input->getOption('exclude-legacy-categories');

            if (!empty($parentExcludeCategories)) {
                foreach ($parentExcludeCategories as $category) {
                    // Check if a category with given id exists, abort otherwise
                    $result = $db->prepare('SELECT id FROM tl_news_category WHERE id=?')->execute($category);

                    if ($result->count() < 1) {
                        $io->error('A legacy category with id '.$category.' to exclude could not be found. Stopping migration to prevent data errors.');

                        return false;
                    }
                }
                $childCategories = $db->getChildRecords($parentExcludeCategories, 'tl_news_category');
                $excludeCategoriesIds = array_merge($parentExcludeCategories, $childCategories);
            }
        }

        if (!empty($newsCategoriesIds)) {
            if (!empty($excludeCategoriesIds)) {
                $newsCategoriesIds = array_diff($newsCategoriesIds, $excludeCategoriesIds);
            }
            $qb->where($qb->expr()->in('c.id', ':categories'))->setParameter('categories', $newsCategoriesIds, Connection::PARAM_INT_ARRAY);
        } elseif (!empty($excludeCategoriesIds)) {
            $qb->where($qb->expr()->notIn('c.id', ':categories'))->setParameter('categories', $excludeCategoriesIds, Connection::PARAM_INT_ARRAY);
        }

        $newsCategories = $qb->execute()->fetchAll();

        if (empty($newsCategories)) {
            $io->success('Found no categories. Finished!');

            return false;
        }

        $newscategoryCount = \count($newsCategories);
        $io->text('Found <fg=yellow>'.$newscategoryCount.'</> categories.');
        $io->newLine();
        $modelUtil = $this->getContainer()->get('huh.utils.model');

        $io->progressStart($newscategoryCount);

        foreach ($newsCategories as $newsCategory) {
            $io->progressAdvance();
            $categoryModel = $modelUtil->setDefaultsFromDca(new CategoryModel());

            // unset the old ID since it may also be available in the target table
            $legacyId = $newsCategory['id'];
            unset($newsCategory['id']);

            $categoryModel->setRow($newsCategory);
            $categoryModel->dateAdded = $newsCategory['tstamp'];

            if (!$this->dryRun) {
                $categoryModel->save();
            }

            if ($categoryModel->id > 0 || $this->dryRun) {
                if ($io->isVerbose()) {
                    $io->text('<info>Successfully migrated category: "'.$categoryModel->title.'" (Legacy-ID: '.$legacyId.')</info>');
                }
                $this->categories[$legacyId] = $categoryModel;

                // store the id mapping
                $this->categoryIdMapping[$legacyId] = $categoryModel->id;
            } else {
                $io->error('Could not migrate category: "'.$categoryModel->title.'" (Legacy-ID: '.$legacyId.')');
            }
        }
        $io->progressFinish();

        // set the correct pid
        foreach ($this->categoryIdMapping as $legacyId => $id) {
            if (null === ($categoryModel = $modelUtil->findModelInstanceByPk('tl_category', $id))) {
                continue;
            }

            if (isset($this->categoryIdMapping[$categoryModel->pid])) {
                $categoryModel->pid = $this->categoryIdMapping[$categoryModel->pid];

                if (!$this->dryRun) {
                    $categoryModel->save();
                }
            }
        }

        return true;
    }

    /**
     * Migrate tl_news_categories to tl_category_association.
     */
    protected function migrateAssociations(SymfonyStyle $io): bool
    {
        $io->section('Start category news association migration.');

        $qb = $this->getQueryBuilder();
        $newsCategoryRelations = $qb->select('c.*')->from('tl_news_categories', 'c')
            ->where($qb->expr()->in('c.category_id', ':cat'))
            ->groupBy('c.category_id,c.news_id')
            ->setParameter('cat', array_keys($this->categoryIdMapping), Connection::PARAM_INT_ARRAY)
            ->execute()->fetchAll();

        if (!$newsCategoryRelations || \count($newsCategoryRelations) < 0) {
            $io->text('Found no category news associations.');

            return true;
        }

        $io->text('Found <fg=yellow>'.\count($newsCategoryRelations).'</> news category associations.');
        $io->newLine();

        $modelUtil = $this->getContainer()->get('huh.utils.model');

        $io->progressStart(\count($newsCategoryRelations));

        foreach ($newsCategoryRelations as $newsCategoryRelation) {
            $io->progressAdvance();

            if (!$this->dryRun && (!isset($this->categories[$newsCategoryRelation['category_id']]) || !isset($this->categoryIdMapping[$newsCategoryRelation['category_id']]))) {
                $io->error('Unable to migrate relation for news with ID:'.$newsCategoryRelation['news_id'].' and category ID:'.$newsCategoryRelation['category_id'].' because category does not exist.');

                continue;
            }

            $categoryAssociationModel = $modelUtil->setDefaultsFromDca(new CategoryAssociationModel());
            $categoryAssociationModel->tstamp = time();
            $categoryAssociationModel->category = $this->categoryIdMapping[$newsCategoryRelation['category_id']];
            $categoryAssociationModel->parentTable = 'tl_news';
            $categoryAssociationModel->entity = $newsCategoryRelation['news_id'];
            $categoryAssociationModel->categoryField = $this->field;

            if (!$this->dryRun) {
                $categoryAssociationModel->save();
            }

            if ($categoryAssociationModel->id > 0 || $this->dryRun) {
                if ($io->isVerbose()) {
                    $io->text('Successfully migrated category relation for field "'.$this->field.'" : "(news ID:'.$newsCategoryRelation['news_id'].')'.$this->categories[$newsCategoryRelation['category_id']]->title.'" (ID: '.$categoryAssociationModel->category.')');
                }
                $this->categoryAssociations[] = $categoryAssociationModel;
            } else {
                $io->error('Could not migrate category relation for field "'.$this->field.'" : "(news ID:'.$newsCategoryRelation['news_id'].')'.$this->categories[$newsCategoryRelation['category_id']]->title.'" (ID: '.$categoryAssociationModel->category.')');
            }
        }
        $io->progressFinish();

        return true;
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->getContainer()->get('doctrine.dbal.default_connection'));
    }
}
