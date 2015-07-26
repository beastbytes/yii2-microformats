# Microformats2 Generator for Yii2

This extension is a Yii2 Widget to generate microformats from model data; also included is a Formatter class for formatting of WGS84 coordinates.

For license information check the [LICENSE](LICENSE.md)-file.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist beastbytes/yii2-microformats
```

or add

```json
"beastbytes/yii2-microformats": "~1.0.0"
```

to the require section of your composer.json.


## Usage

Use this extension in a view in a similar way to [yii\widgets\DetailView](http://www.yiiframework.com/doc-2.0/yii-widgets-detailview.html) (it's a child class of DetailView).

Let's generate an "h-card" from a user model - $user - that has the following relations:

* profile - that stores the user's name
* address - for the user's address
* phone - for the user's phone number

In the view:

```php
echo Microformat::widget([
    'microformat' => 'h-card', // name of the microformat
    'model' => $user, // the model that provides the data
    'attributes' => [ / the attributes array specifies the microformat properties
        'p-name:profile.name', // get the user's name from the profile relation
        'p-email',
        'p-tel:phone.value',        
        [ // Additional markup that is not a microformat property
            'value' => 'Address',
            'template' => '<div class="h-card__title">{value}</div>' // with it's own template
        ],
        [
            'property' => 'p-adr',
            'microformat' => 'h-adr, // Markup the address with an embedded h-adr microformat
            'model' => $user->address, // we can specify a new model for embedded microformats; if not given the parent microformat model is used
            'template' => '<div {options}>{value}</div>', // a new template for h-adr
            'attributes' => [
                'p-street-address', // fetch data from $user->address->street_address
                'p-locality',
                'p-region',
                'p-postal-code',
                [
                    'property' => 'p-geo',
                    'microformat' => 'h-geo',
                    'template' => '<div><span>{label}</span><data {options} value="{rawValue}">{value}</data></div>',
                    'formatter' => [ // use the included formatter for latitude and longitude
                        'class' => '\\beastbytes\\microformats\\Formatter',
                        'coordinateFormat' => '%02d %02.6f h'
                    ],
                    'attributes' => [
                        'property' => 'p-latitude:latitude:latitude', // microformat property:model attribte:format
                        'property' => 'p-longitude:longitude:longitude'
                    ]
                ]
            ]            
        ]
    ]
]);
```

See the source code for more configuration details.
