SwDatabaseConfigBundle
==========================

**Attention!** This bundle was previously know as FlexyDatabaseConfigBundle. Make sure to update your composer.json project file to reflect the name change.

---

SwDatabaseConfigBundle allows you to store configurations from the configuration tree of a bundle and parameters in a database table. Those configurations and parameters will override those defined in the ```app/config/config.yml``` and ```app/config/parameters.yml``` files.

Configurations are all cached using Symfony's container caching mechanism and do not hit the database.

## Content
* Installation
* How to use

## Installation

1. Add this to your composer.json :
```js
    "require": {
        "Sw/database-config-bundle": "dev-master"
    }
```

2. Run a composer update :
```bash
composer update
```

3. Register the bundle in your AppKernel.php :
```php
public function registerBundles()
{
        new Sw\DatabaseConfigBundle\SwDatabaseConfigBundle(),
}
```

4. Update the database schema :
```bash
app/console doctrine:schema:update --force
```

## How to use

### Add a configuration to the database
SwDatabaseConfigBundle reproduces the configuration tree of a bundle in the database table named ```container_config```. If you want to add a configuration in the database table, you have to first add the extension name in the ```container_extension``` table. After that, you will have to add each parent node of the configuration tree that leads to the configuration you have to override.

For example, if you have the following configuration and you want to override ```project_title``` :

```yml
twig:
    globals:
         project_title: My project title
```

First, we have to add ```twig``` to the ```config_extension``` table :

| id  | name | namespace |
| --: | ---- | --------- |
| 1   | twig |           |

Then, we add every node that leads to ```project_title``` in the ```config_configuration``` table :

| id  | parent_id | extension_id | name          | value                |
| --: | --------: | -----------: | ------------- | -------------------- |
| 1   | *NULL*    | 1            | globals       | *NULL*               |
| 2   | 1         | 1            | project_title | My New Project Title |

