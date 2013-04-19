MCNLinkedIn (In development)
============================

Authentication
--------------

To use the authentication adapter you can simply add the trait ```MCNLinkedIn\Entity\ConsumerTrait``` to your
user class and add the following piece of configuration to list of adapters in ```MCNUser```. If you don't have support
for php 5.4 or don't use the trait make sure to update the adapter options accordingly!

```php
'MCNLinkedIn\Options\Authentication\Adapter\LinkedIn' => array(
    // default values
    'entityIdProperty'             => 'linkedInId',
    'entityTokenProperty'          => 'linkedInAccessToken',
    'entityTokenExpiresAtProperty' => 'linkedInTokenExpiresAt'
)
```
