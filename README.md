Laravel SaaS
============

![CI](https://github.com/renoki-co/laravel-saas/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/renoki-co/laravel-saas/branch/master/graph/badge.svg)](https://codecov.io/gh/renoki-co/laravel-saas/branch/master)
[![StyleCI](https://github.styleci.io/repos/277109456/shield?branch=master)](https://github.styleci.io/repos/277109456)
[![Latest Stable Version](https://poser.pugx.org/renoki-co/laravel-saas/v/stable)](https://packagist.org/packages/renoki-co/laravel-saas)
[![Total Downloads](https://poser.pugx.org/renoki-co/laravel-saas/downloads)](https://packagist.org/packages/renoki-co/laravel-saas)
[![Monthly Downloads](https://poser.pugx.org/renoki-co/laravel-saas/d/monthly)](https://packagist.org/packages/renoki-co/laravel-saas)
[![License](https://poser.pugx.org/renoki-co/laravel-saas/license)](https://packagist.org/packages/renoki-co/laravel-saas)

Laravel SaaS is a simple method of managing the SaaS subscriptions at your application level.

It is fully compatible to be used with any version of Cashier.

## 🚀 Installation

You can install the package via composer:

```bash
composer require renoki-co/laravel-saas
```

After installing the package, run the publishing command for migrations & configs:

```bash
$ php artisan vendor:publish
```

## 🙌 Usage

``` php
// app/Providers/LaravelSaasServiceProvider.php

use RenokiCo\LaravelSaas\Saas;

public function boot()
{
    parent::boot();

    Saas::plan('Gold Plan', 'gold-plan')
        ->description('The gold plan.')
        ->price(30, 'EUR')
        ->trial(7, 'day') // 7 days trial
        ->invoice(1, 'month') // monthly subscription
        ->grace(1, 'day'); // grace period
}
```

```php
$plan = Saas::getPlan('gold-plan');

$subscription = $user->newSaasSubscription('main', $plan);
```

## Preparing the plans

You can define the plans at the app service provider level and it will stick throughout the request cycle.

First of all, make sure that you published the files with `vendor:publish` and import the created `app/Providers/LaravelSaasServiceProvider` class into your `app.php`:

```php
$providers = [
    // ...
    \App\Providers\LaravelSaasServiceProvider::class,
];
```

In `LaravelSaasServiceProvider`'s `boot` method you may define the plans you need:

```php
use RenokiCo\LaravelSaas\Saas;

public function boot()
{
    parent::boot();

    Saas::plan('Gold Plan', 'gold-plan')
        ->description('The gold plan.')
        ->price(30, 'EUR')
        ->trial(7, 'day') // 7 days trial
        ->invoice(1, 'month') // monthly subscription
        ->grace(1, 'day'); // grace period
}
```

## Preparing the model

Just like in Cashier, all the models that will make use of the subscriptions should use a trait:

```php
class RenokiCo\LaravelSaas\Traits\HasSubscriptions;

class Team extends Model
{
    use HasSubscriptions;
}
```

Now you can manage the subscriptions using the model itself.

## Attaching plans

```php
$subscription = $user->newSaasSubscription('main', $plan);

$user->subscribedToSaasPlan($plan); // true
```

## Retrieving the subscription

You can retrieve the active subscription by name anytime:

```php
$subscription = $user->activeSaasSubscription('main');
```

## Getting the plan of a subscription

```php
$plan = $subscription->getPlan();
```

## Checking the status

```php
$subscription->active();

$subscription->onTrial();

$subscription->ended();

$subscription->canceled();
```

## Changing a plan

When changing a plan, all the tracked usages will be reverted. Read more about the [Feature Usage Tracking](#feature-usage-tracking)

```php
$plan = Saas::getPlan('silver-plan');

$subscription->changePlan($plan);
```

## Renewing a plan

Renew a plan unless it's ended:

```php
$subscription->renew();
```

## Cancelling a plan subscription

You can cancel a specific subscription anytime. Pass `true` as argument to immediate termination of the plan. Not terminatting immediately, it will be marked at canceled, but will still be active.

```php
$subscription->cancel();
```

## Feature Usage Tracking

You can attach features to the plans:

```php
use RenokiCo\LaravelSaas\Saas;

public function boot()
{
    parent::boot();

    Saas::plan('Gold Plan', 'gold-plan')
        ->features([
            Saas::feature('Build Minutes', 'build.minutes', 3000)
                ->description('3000 build minutes for an entire month'),
        ]);
}
```

Then track them:

```php
$subscription->recordFeatureUsage('build.minutes', 30); // reducing 30 mins

$subscription->getFeatureUsage('build.minutes') // 30
$subscription->getFeatureRemainings('build.minutes') // 2950
```

By default, each created feature is resettable - each time the billing cycle ends, it resets to the starting value (3000 in the previous example).

To avoid resetting, like counting the seats for a subscription, you should call `reset()` on feature, ideally with the plan period:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->invoice(30, 'day')
    ->features([
        Saas::feature('Build Minutes', 'build.minutes', 3000)
            ->description('3000 build minutes for an entire month')
            ->reset(30, 'day'), // matching the invoice period
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
