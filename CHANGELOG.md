# Changelog

All notable changes to `laravel-stripe-webhooks` will be documented in this file

## [Unreleased](https://github.com/spatie/laravel-stripe-webhooks/compare/3.2.1...HEAD)

## [3.2.1](https://github.com/spatie/laravel-stripe-webhooks/compare/3.2.0...3.2.1) - 2022-08-03

### What's Changed

- Add docs for transforming `WebhookCall` payload into Stripe object by @stevebauman in https://github.com/spatie/laravel-stripe-webhooks/pull/129
- allow stripe sdk v9 by @ankurk91 in https://github.com/spatie/laravel-stripe-webhooks/pull/130

### New Contributors

- @stevebauman made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/129

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.2.0...3.2.1

## [3.2.0](https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.3...3.2.0) - 2022-06-07

### What's Changed

- Update UPGRADING.md by @mstaack in https://github.com/spatie/laravel-stripe-webhooks/pull/125
- let user able to define a default job as a catchall event handler by @wanghanlin in https://github.com/spatie/laravel-stripe-webhooks/pull/128

### New Contributors

- @mstaack made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/125
- @wanghanlin made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/128

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.3...3.2.0

## [3.1.3](https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.2...3.1.3) - 2022-05-18

## What's Changed

- Update UPGRADING.md by @flatcapco in https://github.com/spatie/laravel-stripe-webhooks/pull/116
- Update README.md by @flatcapco in https://github.com/spatie/laravel-stripe-webhooks/pull/117
- UPGRADING.md v3: Add payload column type change by @andzandz in https://github.com/spatie/laravel-stripe-webhooks/pull/122
- Allow stripe sdk v8 by @ankurk91 in https://github.com/spatie/laravel-stripe-webhooks/pull/123

## New Contributors

- @flatcapco made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/116
- @andzandz made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/122

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.2...3.1.3

## [3.1.2](https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.1...3.1.2) - 2022-03-07

## What's Changed

- fix: only check for stripe webhooks by @ankurk91 in https://github.com/spatie/laravel-stripe-webhooks/pull/110

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.1...3.1.2

## [3.1.1](https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.0...3.1.1) - 2022-02-05

## What's Changed

- feat: process webhook once by @ankurk91 in https://github.com/spatie/laravel-stripe-webhooks/pull/107

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.1.0...3.1.1

## [3.1.0](https://github.com/spatie/laravel-stripe-webhooks/compare/3.0.2...3.1.0) - 2022-01-14

- allow Laravel 9

## [3.0.2](https://github.com/spatie/laravel-stripe-webhooks/compare/3.0.1...3.0.2) - 2021-11-24

## What's Changed

- Add Unreleased Heading to Changelog by @stefanzweifel in https://github.com/spatie/laravel-stripe-webhooks/pull/101
- docs: fix upgrade guide by @ankurk91 in https://github.com/spatie/laravel-stripe-webhooks/pull/102
- Corrected potential WebhookConfig issues. by @accu-clw in https://github.com/spatie/laravel-stripe-webhooks/pull/104

## New Contributors

- @stefanzweifel made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/101
- @accu-clw made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/104

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.0.1...3.0.2

## [3.0.1](https://github.com/spatie/laravel-stripe-webhooks/compare/3.0.0...3.0.1) - 2021-11-05

## What's Changed

- Update README.md by @Faks in https://github.com/spatie/laravel-stripe-webhooks/pull/98
- Fix publish commands by @ryanito in https://github.com/spatie/laravel-stripe-webhooks/pull/99
- Fix routing by @ankurk91 in https://github.com/spatie/laravel-stripe-webhooks/pull/100

## New Contributors

- @Faks made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/98
- @ryanito made their first contribution in https://github.com/spatie/laravel-stripe-webhooks/pull/99

**Full Changelog**: https://github.com/spatie/laravel-stripe-webhooks/compare/3.0.0...3.0.1

## 3.0.0 - 2021-10-25

- use spatie/laravel-webhook-client v3
- require PHP 8
- require Laravel 8

No changes to the API have been made, so it should be an easy upgrade

## 2.6.2 - 2021-10-04

- Fix model from config not being used

## 2.6.1 - 2021-06-28

- Process everything by default (#89)
- restore Postgres compat

## 2.6.0 - 2021-06-18

- process Stripe calls only once

## 2.5.0 - 2020-01-12

- add `verify_signature` config option

## 2.4.0 - 2020-12-07

- add support for PHP 8

## 2.3.1 - 2020-09-09

- Add Laravel 8 support

## 2.3.0 - 2020-03-03

- Add Laravel 7 support

## 2.2.1 - 2019-09-04

- Allow Stripe 7.x

## 2.2.0 - 2019-09-04

- Add Laravel 6 support

## 2.1.0 - 2019-07-08

- upgrade spatie/laravel-webhook-client from v1 to v2

## 2.0.0 - 2019-06-24

- this package now makes use of spatie/laravel-webhook-client

## 1.2.0 - 2018-02-27

- drop support for Laravel 5.7 and below
- drop support for PHP 7.1 and below

## 1.1.4 - 2018-02-27

- add support for Laravel 5.8

## 1.1.3 - 2018-11-03

- use `STRIPE_WEBHOOK_SECRET` env variable

## 1.1.2 - 2018-08-29

- send a response in the controller

## 1.1.1 - 2018-08-29

- add support for Laravel 5.7

## 1.1.0 - 2018-07-23

- add support for Stripe Connect

## 1.0.3 - 2018-02-17

- add support for stripe api v6

## 1.0.2 - 2018-02-08

- add support for L5.6

## 1.0.1 - 2017-10-12

- added missing parameter to `jobClassDoesNotExist` method

## 1.0.0 - 2017-10-09

- initial release
