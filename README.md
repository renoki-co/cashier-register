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

It helps you define static, project-level plans, and attach them features that can be tracked and limited throughout the app.

For example, you might want to set a limit of `5` seats and to be done internally. CashierRegister comes with a nice wrapper for Laravel Cashier that does that out-of-the-box.

## 🤝 Supporting

Renoki Co. on GitHub aims on bringing a lot of open source, MIT-licensed projects and helpful projects to the world. Developing and maintaining projects everyday is a harsh work and tho, we love it.

If you are using your application in your day-to-day job, on presentation demos, hobby projects or even school projects, spread some kind words about our work or sponsor our work. Kind words will touch our chakras and vibe, while the sponsorships will keep the open source projects alive.

## 🚀 Installation

You can install the package via composer:

```bash
composer require renoki-co/cashier-register
```

After installing the package, run the publishing command for migrations & configs:

```bash
$ php artisan vendor:publish
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

        Saas::plan('Gold Plan', 'plan-price-identifier')
            ->description('The gold plan.')
            ->price(30, 'EUR')
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

The trait already uses the original cashier trait, with small modifications so that you can benefit of CashierRegister's features.

```php
use RenokiCo\CashierRegister\Billable;

class User extends Model
{
    use Billable;

    //
}
```

## Preparing the plans

You can define the plans at the app service provider level and it will stick throughout the request cycle.

First of all, make sure that you published the files with `vendor:publish` and import the created `app/Providers/CashierRegisterServiceProvider` class into your `app.php`:

```php
$providers = [
    // ...
    \App\Providers\CashierRegisterServiceProvider::class,
];
```

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

        // Define plans here.
    }
}
```

**When setting an unique indentifier for the plan (second parameter), make sure to use it from Stripe's plan ID or Paddle Subscription ID.**

Defining plans can also help you retrieving them when showing them in the frontend:

```php
use RenokiCo\CashierRegister\Saas;

$allPlans = Saas::getPlans();

foreach ($allPlans as $plan) {
    $features = $plan->getFeatures();

    //
}
```

Or retrieving a specific plan by Plan ID:

```php
use RenokiCo\CashierRegister\Saas;

$plan = Saas::getPlan('plan-id');
```

Deprecating plans can occur anytime. In order to do so, just call `deprecated()` when defining the plan:

```php
/**
 * Boot the service provider.
 *
 * @return void
 */
public function boot()
{
    parent::boot();

    Saas::plan('Silver Plan', 'silver-plan-id')
        ->deprecated();
}
```

As an alternative, you can anytime retrieve the available plans only:

```php
use RenokiCo\CashierRegister\Saas;

$plans = Saas::getAvailablePlans();
```

## Feature Usage Tracking

You can attach features to the plans:

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

$subscription->getUsedQuota('build.minutes') // 30
$subscription->getRemainingQuota('build.minutes') // 2950
```

## Checking overflow

Checking overflow can be useful when users fallback from a bigger plan to an older plan. In this case, you may end up with an overflow case where the users will have feature tracking values greater than the smaller plan values.

You can check if the feature value overflown by calling `featureOverQuota`:

```php
$subscription->swap($freePlan); // has no build minutes

// Will return true if the consumed build minutes are greater than the free plan (0 minutes)
$subscription->featureOverQuota('build.minutes');
```

## Resetting tracked values

By default, each created feature is resettable - each time the billing cycle ends, you can call `resetQuotas` to reset them (they will become 3000 in the previous example).

Make sure to call `resetQuotas` after the billing cycle resets.

For example, you can extend the default Stripe Webhook controller that Laravel Cashier comes with and implement the `invoice.payment_succeeded` event handler:

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

To avoid resetting, like counting the seats for a subscription, you should call `notResettable()` on the feature:

```php
Saas::plan('Gold Plan', 'gold-plan')
    ->features([
        Saas::feature('Seats', 'seats', 5)->notResettable(),
    ]);
```

Now when calling `resetQuotas()`, the `seats` feature won't go back to the default value.

## Unlimited amount

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
