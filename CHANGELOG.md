# Changelog

All notable changes to `laravel-multitenancy` will be documented in this file

## 1.6.11 - 2020-12-17

- allow PHP 8

## 1.6.10 - 2020-12-03

- NeedsTenant ability to return or redirect

## 1.6.9 - 2020-09-27

- fix for BroadcastEvent (#142)

## 1.6.8 - 2020-09-24

- add ability to dispatch events tenant aware

## 1.6.7 - 2020-09-07

- add support for Laravel 8

## 1.6.6 - 2020-08-24

- restored check isCurrent from makeCurrent

## 1.6.5 - 2020-08-21

- 🐛 removed check isCurrent from makeCurrent

## 1.6.4 - 2020-08-11

- forget current when making new tenant current

## 1.6.3 - 2020-08-07

- removed $guarded from Tenant model

## 1.6.2 - 2020-08-07

- TenantAware now uses the Tenant model from config

## 1.6.1 - 2020-08-06

- TenantsArtisanCommand now uses TenantAware trait

## 1.6.0 - 2020-08-06

- added `TenantAware`

## 1.5.1 - 2020-07-30

- 🐛 properly handle queued mailables and notification (#78)

## 1.5.0 - 2020-07-23

- database switch fails with misconfigured tenant (#92)

## 1.4.0 - 2020-07-02

- added `execute` for the landlord

## 1.3.0 - 2020-06-25

- added `$tenant->execute($callable)` to execute isolated tenant code (#60)

## 1.2.0 - 2020-06-21

- `tenant:artisan` search field customizable using config (#52)

## 1.1.5 - 2020-06-21

- allow mass assignment when creating a new tenant by default (#57)

## 1.1.4 - 2020-06-17

- improve error handling of tenant aware jobs

## 1.1.3 - 2020-06-01

- always register the tenants artisan command, so it may be called from web requests as well.

## 1.1.2 - 2020-05-24

- remove unused import from config (#20)

## 1.1.1 - 2020-05-21

- use the configured tenant model in artisan command (#17)

## 1.1.0 - 2020-05-18

- fix published migration name
- ask for artisan command if none is given

## 1.0.0 - 2020-05-16

- initial release
