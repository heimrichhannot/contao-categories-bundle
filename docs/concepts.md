# Concepts

## Primary category

When assigning a multiple category (checkbox) field to your DCA, you might want the editor to specify to mark some of the categories as the *primary* one.
This is e.g. necessary if some field (e.g. jumpTo) can be overridden by a concrete category.

## Overridable properties

You can specify special *overridable properties* in one of the following objects (highest number is highest priority):

1. a *category*
2. a *category config* assigned to the *category* in 1.
3. a *sub category* of the *category* in 1.
4. a *category config* assigned to the *category* in 3.

*HINT 1: The steps might be happening recursively if you have more than 1 hierarchy levels.*

*HINT 2: Using category configs is not mandatory. Simple overriding in sub categories works anyway.*

For simple illustration, consider the following:

Category A is the root category and has a child category B. Category B has again the child category C. Then the priority would be as follows:

*(Given that the context triggers the category configs)*

`category config of C > category C > category config of B > category B > category config of A > category A`

Of if you don't want to use category configs, it's

`category C > category B > category A`

### Retrieving the context sensitive property value

After defining this hierarchy, you can easily retrieve the context sensitive property value by calling

```
$value = \System::getContainer()->get('huh.categories.manager')->getOverridableProperty(
    <propertyName>, <some context object containing a field-to-context-mapping>, <category/categories field name>, <primary category id>
);
```

*HINT: Of course, you don't have to use `getOverridableProperty()`. You can also specify your own inheritance logic.*

You now might ask, how it is decided which of the category configs is used and what the purpose of the *context object* is. See the example below:

### Example

Consider the following requirements:

1. You want to implement two news lists on 2 different pages.
2. The news on page 1 are in news archive 1 and the news on page 2 are in news archive 2.
3. But: All news share the same categories.
4. Now you want to have different jumpTo urls based on:
     1. the concrete category assigned to the news, i.e. the jumpTo is defined in the category
     2. the jumpTo differs with the news archive *in combination with the category*, i.e. if a news has some category *and is in news archive 1*, it should have a different jumpTo than if it had the same category and was in news archive 2

This is not possible using the classic assignment of jumpTo's to the news archives. In fact you would end up with duplicate content management (evil!).

Now how do you implement that? -> see the next chapter "Technical instructions"