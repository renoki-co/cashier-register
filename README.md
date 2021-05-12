Cashier Register - Track the plan quotas
===========================================

![CI](https://github.com/renoki-co/cashier-register/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/renoki-co/cashier-register/branch/master/graph/badge.svg)](https://codecov.io/gh/renoki-co/cashier-register/branch/master)
[![StyleCI](https://github.styleci.io/repos/277109456/shield?branch=master)](https://github.styleci.io/repos/277109456)
[![Latest Stable Version](https://poser.pugx.org/renoki-co/cashier-register/v/stable)](https://packagist.org/packages/renoki-co/cashier-register)
[![Total Downloads](https://poser.pugx.org/renoki-co/cashier-register/downloads)](https://packagist.org/packages/renoki-co/cashier-register)
[![Monthly Downloads](https://poser.pugx.org/renoki-co/cashier-register/d/monthly)](https://packagist.org/packages/renoki-co/cashier-register)
[![License](https://poser.pugx.org/renoki-co/cashier-register/license)](https://packagist.org/packages/renoki-co/cashier-register)

Cashier Register is a simple quota feature usage tracker for Laravel Cashier subscriptions.

It helps you define static, project-level plans, and attach them features that can be tracked and limited throughout the app. For example, you might want to set a limit of `5` seats per team and make it so internally. Cashier Register comes with a nice wrapper for Laravel Cashier that does that out-of-the-box.

- [Cashier Register - Track the plan quotas](#cashier-register---track-the-plan-quotas)
  - [🤝 Supporting](#-supporting)
  - [🚀 Installation](#-installation)
    - [Cashier for Stripe](#cashier-for-stripe)
    - [Cashier for Paddle](#cashier-for-paddle)
  - [🙌 Usage](#-usage)
  - [🏧 Preparing the model](#-preparing-the-model)
  - [💰 Preparing the plans](#-preparing-the-plans)
    - [Installing the Service Provider](#installing-the-service-provider)
    - [Defining the plans](#defining-the-plans)
    - [Defining yearly plans](#defining-yearly-plans)
    - [Retrieving the plans](#retrieving-the-plans)
    - [Deprecating/Archiving plans](#deprecatingarchiving-plans)
    - [Setting the plan as popular](#setting-the-plan-as-popular)
  - [Plan Features](#plan-features)
    - [Checking Exceeded Quotas](#checking-exceeded-quotas)
    - [Catching Mid-Exceed Quotas](#catching-mid-exceed-quotas)
    - [Resetting tracked values](#resetting-tracked-values)
    - [Unlimited amounts](#unlimited-amounts)
    - [Inherit features from other plans](#inherit-features-from-other-plans)
    - [Additional data](#additional-data)
  - [Stripe-specific Features](#stripe-specific-features)
    - [Stripe Metered Billing with Mid-Exceed Quotas](#stripe-metered-billing-with-mid-exceed-quotas)
    - [Metered Features](#metered-features)
  - [Static items](#static-items)
  - [🐛 Testing](#-testing)
  - [🤝 Contributing](#-contributing)
  - [🔒  Security](#--security)
  - [🎉 Credits](#-credits)

## 🤝 Supporting

Renoki Co. on GitHub aims on bringing a lot of open source projects and helpful projects to the world. Developing and maintaining projects everyday is a harsh work and tho, we love it.

If you are using your application in your day-to-day job, on presentation demos, hobby projects or even school projects, spread some kind words about our work or sponsor our work. Kind words will touch our chakras and vibe, while the sponsorships will keep the open source projects alive.

[![ko-fi](https://www.ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/R6R42U8CL)

## 🚀 Installation

You can install the package via composer:

```bash
composer require renoki-co/cashier-register
```

Publish the config file:

```bash
$ php artisan vendor:publish --provider="RenokiCo\CashierRegister\CashierRegisterServiceProvider" --tag="config"
```

Publish the migrations:

```bash
$ php artisan vendor:publish --provider="RenokiCo\CashierRegister\CashierRegisterServiceProvider" --tag="migrations"
```

The package does not come with Cashier as dependency, so you should install according to your needs:

### Cashier for Stripe

```
$ composer require laravel/cashier:"^12.13"
```

### Cashier for Paddle

```
$ composer require laravel/cashier-paddle:"^1.4.4"
```

## 🙌 Usage

``` php
use RenokiCo\CashierRegister\CashierRegisterServiceProvider as BaseServiceProvider;
use RenokiCo\CashierRegister\Saas;

class CashierRegisterServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'price_from_stripe_mo...', 'yearly_price_id')
            ->monthly(30)
            ->yearly(300)
            ->currency('EUR')
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 3000),
            ]);
    }
}
```

To meter a specific feature usage, you simply may call:

```php
$user->subscription('main')->recordFeatureUsage('build.minutes', 30);
```

## 🏧 Preparing the model

For billables, you should follow the installation instructions given with Cashier for Paddle or Cashier for Stripe.

This package already sets the custom `Subscription` model. In case you want to add more functionalities to the Subscription model, make sure you extend accordingly from these models:

- Paddle: `RenokiCo\CashierRegister\Models\Paddle\Subscription`
- Stripe: `RenokiCo\CashierRegister\Models\Stripe\Subscription`

Further, make sure you check the `saas.php` file and replace the subscription model from there, or you can use the `::useSubscriptionModel` call in your code.

Cashier Register already does that for you in the background, but feel free to replace them with your models if you need to.

## 💰 Preparing the plans

### Installing the Service Provider

You can define the plans at the app service provider level and it will stick throughout the request cycle.

First of all, publish the Provider file:

```bash
$ php artisan vendor:publish --provider="RenokiCo\CashierRegister\CashierRegisterServiceProvider" --tag="provider"
```

Import the created `app/Providers/CashierRegisterServiceProvider` class into your `app.php`:

```php
$providers = [
    // ...
    \App\Providers\CashierRegisterServiceProvider::class,
];
```

### Defining the plans

In `CashierRegisterServiceProvider`'s `boot` method you may define the plans you need:

```php
use RenokiCo\CashierRegister\CashierRegisterServiceProvider as BaseServiceProvider;
use RenokiCo\CashierRegister\Saas;

class CashierRegisterServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'price_...')
            ->monthly(30)
            ->currency('EUR')
            ->features([
                Saas::feature('Seats', 'seats', 5)->notResettable(),
            ]);
    }
}
```

Please note that the `::plan` method accepts a display name, and the following two parameters are that **plan identifiers in either Stripe or Paddle**. This will ensure the available plans have unique IDs and they will be processed accordingly:

```php
use RenokiCo\CashierRegister\CashierRegisterServiceProvider as BaseServiceProvider;
use RenokiCo\CashierRegister\Saas;

class CashierRegisterServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'price_monthly');
    }
}
```

### Defining yearly plans

By default, yearly plans are not included but you might specify it:

```php
use RenokiCo\CashierRegister\CashierRegisterServiceProvider as BaseServiceProvider;
use RenokiCo\CashierRegister\Saas;

class CashierRegisterServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'price_monthly', 'price_yearly');
    }
}
```

### Retrieving the plans

```php
use RenokiCo\CashierRegister\Saas;

$allPlans = Saas::getPlans();

foreach ($allPlans as $plan) {
    $features = $plan->getFeatures();

    //
}
```

When retrieving a specific plan by Plan ID, you may pass the identifier:

```php
use RenokiCo\CashierRegister\Saas;

$plan = Saas::getPlan('price_monthly');
```

Please note that if the yearly plan ID is defined, you can also retrieved by it. However, the `->getId()` and `->getYearlyid()` are different:

```php
use RenokiCo\CashierRegister\Saas;

$plan = Saas::getPlan('price_yearly');

$plan->getId(); // price_monthly
$plan->getYearlyId(); // price_yearly
```

### Deprecating/Archiving plans

Deprecating plans can occur anytime. In order to do so, just call `deprecated()` when defining the plan:

```php
use RenokiCo\CashierRegister\Saas;

/**
 * Boot the service provider.
 *
 * @return void
 */
public function boot()
{
    parent::boot();

    Saas::plan('Silver Plan', 'silver-plan-id')->deprecated();
}
```

As an alternative, you can anytime retrieve the available plans only:

```php
use RenokiCo\CashierRegister\Saas;

$plans = Saas::getAvailablePlans();
```

### Setting the plan as popular

Some plans are popular among others, and you can simply mark them:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->popular();
```

This will add a data field called `popular` that is either `true/false`.

## Plan Features

You can attach features to the plans and thus you can check usage for each subscription:

```php
use RenokiCo\CashierRegister\CashierRegisterServiceProvider as BaseServiceProvider;
use RenokiCo\CashierRegister\Saas;

class CashierRegisterServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Saas::plan('Gold Plan', 'price_gold')->features([
            Saas::feature('Seats', 'seats', 5)->notResettable(),
        ]);
    }
}

$subscription->recordFeatureUsage('seats', 3); // 3 new users joined

$subscription->getUsedQuota('seats'); // 3
$subscription->getRemainingQuota('seats') // 2
```

### Checking Exceeded Quotas

Checking exceeded quotas can be useful when users fallback from a bigger plan to an older plan. In this case, you may end up with an exceeded quota case where the users will have feature tracking values greater than the smaller plan values.

Before swapping, you might check the features from the lower plan and get the list of features that need to be handled:

```php
$freePlan = Saas::plan('Free Plan', 'price_free'); // already subscribed to this plan
$paidPlan = Saas::plan('Paid Plan', 'price_paid');

$overQuotaFeatures = $subscription->featuresOverQuotaWhenSwapping($paidPlan->getId());

// If no features are over quotas before swapping, apply the plan swap.
if ($overQuotaFeatures->count() === 0) {
    $subscription->swap($freePlan);
}

foreach ($overQuotaFeatures as $feature) {
    // $feature->getName();
}
```

### Catching Mid-Exceed Quotas

Naturally, `recordFeatureUsage()` has a callback parameter that gets called whenever the amount of consumption gets over the allocated total quota right when recording the usage.

For example, customers can have 5 seats in total, but when all of 5 are met, you might want to re-check the amount of exceeded quota and billing separately:

```php
$subscription->recordFeatureUsage('seats', 5); // 5 new users joined, 0 seats remaining

$subscription->recordFeatureUsage('seats', 3, true, function ($feature, $valueOverQuota, $subscription) {
    $this->billUserForExtraSeats($subscription->model, $valueOverQuota);
});
```

### Resetting tracked values

Each created feature is resettable - each time the billing cycle ends, you can call `resetQuotas` on the subscription to reset them.

You can have:

- consumable features, for example the amount of mails your client can send each month via your newsletter service
- non-resettable features, like team seats, the amount of maximum projects at a time, etc.

The appropriate way is to be able to reset the quotas after each billing cycle. With Stripe, you might want to implement a Webhook controller listening to the `invoice.payment_succeeded` event:

```php
<?php

use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeController extends WebhookController
{
    /**
     * Handle invoice payment succeeded.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $data = $payload['data']['object'];

            $subscription = $user->subscriptions()
                ->whereStripeId($data['subscription'] ?? null)
                ->first();

            if ($subscription) {
                $subscription->resetQuotas();
            }
        }

        return $this->successMethod();
    }
}
```

To avoid resetting, you may call `notResettable()` on the feature. This way, the quota reset won't occur on the `seats` feature.

```php
Saas::plan('Gold Plan', 'gold-plan')->features([
    Saas::feature('Seats', 'seats', 5)->notResettable(),
]);
```

### Unlimited amounts

To set an infinite amount of usage, use the `unlimited()` method. You can consume as much as you want from the feature, and it will never exceed the quota:

```php
Saas::plan('Gold Plan', 'gold-plan')->features([
    Saas::feature('Seats', 'seats')->unlimited(),
]);
```

### Inherit features from other plans

You may copy the base features from a given plan and overwrite same-ID features for new plans.

In the following example, the `Paid Plan` has unlimited seats (compared with the 10 seats per Free Plan) and a new feature called `beta.access` that is exclusively for the paid plan.

```php
$freePlan = Saas::plan('Free Plan', 'free-plan')->features([
    Saas::feature('Seats', 'seats')->value(10),
]);

$paidPlan = Saas::plan('Paid Plan', 'paid-plan')->inheritFeaturesFromPlan($freePlan, [
    Saas::feature('Seats', 'seats')->unlimited(), // same-ID features are replaced
    Saas::feature('Beta Access', 'beta.access')->unlimited(), // new IDs are merged
]);
```

**Keep in mind, avoid using further `->features()` when inheriting from another plan.**

### Additional data

Items, plans and features implement a `->data()` method that allows you to attach custom data for each item:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->data(['golden' => true])
    ->features([
        Saas::feature('Seats', 'seats')
            ->data(['digital' => true])
            ->unlimited(),
    ]);

$plan = Saas::getPlan('gold-plan');
$feature = $plan->getFeature('seats');

$planData = $plan->getData(); // ['golden' => true]
$featureData = $feature->getData(); // ['digital' => true]
```

## Stripe-specific Features

### Stripe Metered Billing with Mid-Exceed Quotas

When exceeding the allocated quota for a specific feature when recording, [Metered Billing for Stripe](#metered-features) comes in and bills for extra metered usage, but only if the feature is defined as [Metered Feature](#metered-features).

```php
Saas::plan('Gold Plan', 'gold_price')->features([
    Saas::meteredFeature('Seats', 'seats', 5), // included: 5
        ->meteredPrice('price_metered_identifier', 3, 'seat'), // on-demand: $0.01/minute
]);

$subscription->recordFeatureUsage('seats', 3); // 3 new users joined, 2 seats remaining

$subscription->recordFeatureUsage('seats', 4, true, function ($feature, $valueOverQuota, $subscription) {
    // From the used 3 seats, 5 are free. It remains only 2 seats.
    // The user wants another 4 seats, so Stripe Metered Billing is going to bill only 2, the remaining over quota.

    // Here you can run custom logic to handle overflow. The metered billing usage report was already done.
});
```

### Metered Features

Metered features are opened for Stripe only and this will open up custom metering for exceeding quotas on features.

You might want to give your customers a specific amount of a feature, let's say `Build Minutes`, but for exceeding amount of minutes you might invoice at the end of the month a price of `$0.01` per minute:

```php
Saas::plan('Gold Plan', 'gold-plan')->features([
    Saas::meteredFeature('Build Minutes', 'build.minutes', 3000), // included: 3000
        ->meteredPrice('price_identifier', 0.01, 'minute'), // on-demand: $0.01/minute
]);
```

If you simply want just the on-demand price of the metered feature, just omit the amount:

```php
Saas::plan('Gold Plan', 'gold-plan')->features([
    Saas::meteredFeature('Build Minutes', 'build.minutes'), // included: 0
        ->meteredPrice('price_identifier', 0.01, 'minute'), // on-demand: $0.01/minute
]);
```

**The third parameter is just a conventional name for the unit. `0.01` is the price per unit (PPU). In this case, it's `minute` and `$0.01`, assuming the plan's price is in USD.**

## Static items

In case you are not using plans, you can describe items once in Cashier Register's service provider and then leverage it for some neat usage:

```php
Saas::item('Elephant Sticker', 'elephant-sticker')
    ->price(5, 'EUR');
```

Then later be able to retrieve it:

```php
$item = Saas::getItem('elephant-sticker');

$item->getPrice(); // 5
$item->getCurrency(); // 'EUR'
```

Each item can have sub-items too:

```php
Saas::item('Sticker Pack', 'sticker-pack')
    ->price(20, 'EUR')
    ->subitems([
        Saas::item('Elephant Sticker', 'elephant-sticker')->price(5, 'EUR'),
        Saas::item('Zebra Sticker', 'zebra-sticker')->price(10, 'EUR'),
    ]);

$item = Saas::getItem('sticker-pack');

foreach ($item->getSubitems() as $item) {
    $item->getName(); // Elephant Sticker, Zebra Sticker, etc...
}
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
