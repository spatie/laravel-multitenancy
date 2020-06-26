# Changelog

All notable changes to `laravel-multitenancy` will be documented in this file

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
