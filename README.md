Fuel - Laravel Cashier feature usage tracker
============================================

![CI](https://github.com/renoki-co/fuel/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/renoki-co/fuel/branch/master/graph/badge.svg)](https://codecov.io/gh/renoki-co/fuel/branch/master)
[![StyleCI](https://github.styleci.io/repos/277109456/shield?branch=master)](https://github.styleci.io/repos/277109456)
[![Latest Stable Version](https://poser.pugx.org/renoki-co/fuel/v/stable)](https://packagist.org/packages/renoki-co/fuel)
[![Total Downloads](https://poser.pugx.org/renoki-co/fuel/downloads)](https://packagist.org/packages/renoki-co/fuel)
[![Monthly Downloads](https://poser.pugx.org/renoki-co/fuel/d/monthly)](https://packagist.org/packages/renoki-co/fuel)
[![License](https://poser.pugx.org/renoki-co/fuel/license)](https://packagist.org/packages/renoki-co/fuel)

Fuel is a simple feature usage tracker for Laravel Cashier.

It helps you define static, project-level plans, and attach them features that can be tracked and limited throughout the app.

For example, you might want to set a limit of `5` seats and to be done internally. Fuel comes with a nice wrapper for Laravel Cashier that does that out-of-the-box.

## 🚀 Installation

You can install the package via composer:

```bash
composer require renoki-co/fuel
```

After installing the package, run the publishing command for migrations & configs:

```bash
$ php artisan vendor:publish
```

## 🙌 Usage

``` php
use RenokiCo\Fuel\FuelServiceProvider as BaseServiceProvider;
use RenokiCo\Fuel\Saas;

class FuelServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'gold-plan')
            ->description('The gold plan.')
            ->price(30, 'EUR')
            ->trial(7, 'day') // 7 days trial
            ->invoice(1, 'month') // monthly subscription
            ->grace(1, 'day') // grace period
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000)
                    ->description('3000 build minutes for an entire month!'),
            ]);
    }
}
```

```php
$user->subscription('main')
    ->recordFeatureUsage('build.minutes', 30);
```

## Preparing the model

Instead of using Cashier's Billing trait, you should use the trait that comes with this package.

The trait already uses the original cashier trait, with small modifications so that you can benefit of Fuel's features.

```php
use RenokiCo\Fuel\Billable;

class User extends Model
{
    use Billable;

    //
}
```

## Preparing the plans

You can define the plans at the app service provider level and it will stick throughout the request cycle.

First of all, make sure that you published the files with `vendor:publish` and import the created `app/Providers/FuelServiceProvider` class into your `app.php`:

```php
$providers = [
    // ...
    \App\Providers\FuelServiceProvider::class,
];
```

In `FuelServiceProvider`'s `boot` method you may define the plans you need:

```php
use RenokiCo\Fuel\FuelServiceProvider as BaseServiceProvider;
use RenokiCo\Fuel\Saas;

class FuelServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Define plans here.
    }
}
```

## Feature Usage Tracking

You can attach features to the plans:

```php
use RenokiCo\Fuel\FuelServiceProvider as BaseServiceProvider;
use RenokiCo\Fuel\Saas;

class FuelServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'gold-plan')
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000)
                    ->description('3000 build minutes for an entire month!'),
            ]);
    }
}
```

Then track them:

```php
$subscription->recordFeatureUsage('build.minutes', 30); // reducing 30 mins

$subscription->getFeatureUsage('build.minutes') // 30
$subscription->getFeatureRemainings('build.minutes') // 2950
```

By default, each created feature is resettable - each time the billing cycle ends, it resets to the starting value (3000 in the previous example).

Make sure to set the reset time exactly how long the invoice period is for the plan:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->invoice(30, 'day')
    ->features([
        Saas::feature('Build Minutes', 'build.minutes', 3000)
            ->description('3000 build minutes for an entire month')
            ->reset(30, 'day'),
    ]);
```

To avoid resetting, like counting the seats for a subscription, you should call `notResettable()` on the feature:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->invoice(30, 'day')
    ->features([
        Saas::feature('Seats', 'seats', 5)->notResettable(),
    ]);
```

To set an infinite amount of usage, use the `unlimited()` method:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->features([
        Saas::feature('Seats', 'seats')->unlimited(),
    ]);
```

## 🐛 Testing

``` bash
vendor/bin/phpunit
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒  Security

If you discover any security related issues, please email alex@renoki.org instead of using the issue tracker.

## 🎉 Credits

- [Alex Renoki](https://github.com/rennokki)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
