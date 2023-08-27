# Qubus Router

Qubus router is a powerful and fast PHP router for PSR-7/PSR-15 messages.

## Features

* Basic routing (`GET`, `HEAD`, `POST`, `PUT`, `PATCH`, `UPDATE`, `DELETE`) with support for custom multiple HTTP verbs.
* Regular expression constraints for parameters.
* Named routes.
* Generating url to routes.
* Route parameters.
* Optional route parameters.
* Route groups.
* PSR-7/PSR-15 Middlewares (classes that intercepts before the route is rendered) for routes, groups and controllers.
* Responsable objects.
* Domain/Subdomain routing
* Custom boot managers to rewrite urls
* Option to load routes from JSON file
* and more . . .

## Requirements
* PHP 8.2+

## Installation

```
composer require qubus/router
```

## Rewrite Rules

### Apache

```
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
</IfModule>
```

### Nginx
```
location / {
    try_files $uri /index.php;
}
```

## More Info
- [Documentation](https://docs.qubusphp.com/routing/)
- [Contributing](https://docs.stalframework.com/contributing/)
