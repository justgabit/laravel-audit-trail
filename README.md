# Audit Trail package for Laravel 5+


AuditTrail allows the creation of an audit trail (a detailed log) of any domain transactions. Currently it only works with a database but the table name and the connection name can be customized and many different tables can be used at once. This is useful if you need to create different kinds of audit trails in the same app (system transactions vs user transactions, etc).


## Installation

Since this is a private package, extra steps must be taken in order for composer to be able to install it properly.

First, add this repository to your `composer.json` file. This example shows a `repositories` key with a single item but you may have others too:

    "repositories": [
      {
        "type": "vcs",
        "url":  "https://bitbucket.org/mueva/laravel-audit-trail.git"
      }
    ]

This tells composer that the `mueva/laravel-audit-trail` is hosted in a repository in BitBucket. You now need to add authentication to that repository, otherwise you will not be able to clone it. For this you will need to create an `auth.json` file in the same directory as your `composer.json` and fill it with the OAuth credentials for the user that you want to clone the repo with. 

    {
      "bitbucket-oauth": {
        "bitbucket.org": {
          "consumer-key": "<INSERT KEY HERE>",
          "consumer-secret": "<INSERT SECRET HERE>"
        }
      }
    }

OAuth credentials for a BitBucket user can be found in (User avatar lower left icon) > Settings > Access Management > OAuth > Add consumer. The urls in that page can be anything and are not used by composer.

IMPORTANT: The user cloning this repository needs only read-only access so it can be YOUR same user credentials in this file. But make absolutely sure that you **NEVER COMMIT `auth.json`** as your credentials will be exposed. A deployment user will need to be granted read-only access to this repository and their credentials will need to be added to the server where this package is deployed. Otherwise, this package will not be able to be deployed in production and composer install in production will fail.

Once the repository is added to `compsoer.json` and the `auth.json` file is created you can execute the following line to add this repo to your project:

    composer require mueva/laravel-audit-trail 

AuditTrail comes with two database tables that you will need to create in order to use it. After the package is installed you will need to create the tables:

    php artisan migrate

There will be two tables that are created `audit_trail` and `audit_trail_actions`. You may with to change the names of these tables by editing the package default configuration (more on this later) before running the migrations.

## Concepts

An audit trail is a chronological list of "actions". It is meant to be read and written but NEVER updated or deleted. Whatever happened in the system should live in the audi trail log forever.

**Actions** represent a domain transaction that you want to track. in other words, an action is something that happens in your app that you want to have a record of. Every audit trail that you generate will *always* be based on an action. The following are examples of actions you might have:

* Login
* Failed login
* Logout
* Record update
* Purchase email sent

Each action must be present in the `audit_trail_actions` table but AuditTrail will automatically update this table for you as new actions are created. Still it is a good idea to pre-populate this table with all the actions you will be having in order to avoid race conditions while running in production. The actions table also has a `description` field where you can describe what the action represents and what data it holds. It is entirely optional and it is not used at all within AuditTrail internally.

To create an action you will need to create a class that extends `Mueva\AuditTrail\Action`. The action `id` in the `audit_trail_actions` table will be derived from what you call your class using kebab case. If your class is called `UpdateRecord` then the `id` for that action will become `update-record`.

The `audit_trail` table contains the following fields by default:

* **id**: Primary key of the table. 
* **parent_id**: A foreign key referencing `id`. When a row contains a non-null `parent_id`, that row is considered to be a child row. This allows for tracking related transactions, for example, everything that happened in a single HTTP request. The first audit trail row gets an `id` and the following rows contain the first row's id as their parent. 
* **action_id**: A foreign key referencing the `audit_trail_actions` table. This foreign key is set to cascade on update. If you ever need to rename an action, you can rename it in the parent table and all the rows in the `audit_trail` table will be renamed accordingly.
* **user_id**: This represents a user id in your own system. Every action is most likely a the result of a user interaction so this allows you to track which user triggered the action. If you have actions that happen without user interaction, like cron jobs, you can set this to null.
* **action_data**: This is a json object that is returned by your action class. You can fill this with whatever you want that will help you gain context into the action that happened. For example, if your action represents a record in your database being modified, you can fill this field with the diff of what changed, or even a before and after snapshot of the record.
* **action_brief**: This is a brief recollection of what happened during the action. For example, if your `action_data` contains the full row that was just updated, the `action_brief` might contain something like "User @JohnDoe updated row #89 (added +2 units) on the products table". In other words, a human readable short recolection of what the action. 
* **table_name**: If your action represents a change in one of your system's tables, this is where you indicate in which table the change happened. If the action represents something else that is not table related, then this can be null.
* **table_id**: Just like with `table_name`, this represents the ID of a row that has had a change. So if your action represents deleting the row 30 on the products table, then `table_name` will contain "products" and `table_id` will contain 30. 
* **date**: Date the action happened. 

## Config

AuditTrail publishes a configuration file that can be overwritten by the application that consumes it. In order to publish the config to your Laravel install, do this:

    php artisan vendor:publish

A menu will appear with all the packages that have something available for publishing. Choose the one that contains the `Mueva\AuditTrail\AuditTrailServiceProvider`. A new configuration file `app/config/audit-trail.php` will be available to you. Inside you will find documentation on each of the options available.

## Creating an Action

As stated earlier, every action must extend `Mueva\AuditTrail\Action`. The best place to know about the options available is the class itself. Every method is documented and should give you a good idea of what to do. Since the abstract action class extends PHP's `JsonSerializable`, the only required method that you must implement is `jsonSerialize()`. The output of this method is exactly what will be saved in `audit_action` field in the `audit_trail` table.

Other than what's on the class methods documentation, there is one notable thing to mention. If your action represents an Eloquent model, then there is no need to override the `getTableName()` and `getTableId()` methods as those can be deduced from the Eloquent model itself. In that case, just return the model object in `getEloquentModel()` and AuditTrail will automatically fill the `table_name` and `table_id` fields automatically.

## API

Using AuditTrail should be very straight forward. Let's assume that you have already created a Login action (more on how to do this later) and that your login action.

At the top of your class, import AuditTrail and your action class:

```php
use Mueva\AuditTrail\AuditTrail;
use App\AuditTrailActions\Login;
```

The most detailed functionality would be:

```php
AuditTrail::create()
    ->userId(23)
    ->action(new ProductChanged($productModel))
    ->connection('mysql')
    ->table('my_custom_audit_trail_table')
    ->execute();
```

This tells AuditTrail to use the `mysql` connection and create a row in the `my_custom_audit_trail_table`. Both of these calls are optionals and when you omit them, AUditTrail will use the configured defaults. In this case we are specifying that the user that executed the action is identified by id 23 and that the action itself represents a product that has changed. In this example `ProductChange` is a userland action that extends `Mueva\AuditTrail\Action`.

You can call `userId(null)` for system actions that aren't triggered by a particular user. Also, `userId()` can be ommited completely and AuditTrail will attempt to autodetect the user with an internal call to `Auth::getUser()`, so if you are using Laravel's default authentication mechanisms, omitting `userId()` can be a viable shortcut.

### Successive calls to AuditTrail

Let's say that you have three calls to AuditTrail in your code. They don't necessarily need to be in the same method, class, or even scope:

```php
AuditTrail::create()
    ->action(new Login($user))
    ->execute();

AuditTrail::create()
    ->action(new UpdateCart($product))
    ->execute();

AuditTrail::create()
    ->action(new UpdateCart($product))
    ->execute();
```

In this case, the second and third actions will have a populated `parent_id` field with the `id` of the first row. This allows you to query the audit trail and have more information on what actions triggered or are related to the one you are looking at.

If you wish for an action to be a "parent" row, that is, for its `parent_id` to be `null` then you can call `resetParent()`:

```php
AuditTrail::create()
    ->action(new Login($user))
    ->execute();

AuditTrail::create()
    ->resetParent()
    ->action(new UpdateCart($product))
    ->execute();

AuditTrail::create()
    ->action(new UpdateCart($product))
    ->execute();
```

As you can see, the second call now uses `resetParent()`. This means that the first and second calls will be parent rows and the third will have *the second* row as its parent. A row can only have exactly one parent.

## Additional fields in the audit_trail table

You can manually add as many extra fields to the `audit_trail` table as you like. When generating an audit trail, you can pass extra parameters to the call to populate these fields. Let's say we wanted to track what virtual machine instance the action has taken place in. We would add a `instance` column to the `audit_trail` table, and the fill it like this:

```php
AuditTrail::create()
    ->action(new Login($user))
    ->instance('i-ys3abwk248sla')
    ->execute();
```

You can chain as many extra fields as you need.