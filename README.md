# book
PHP application providing patron interface to Spaces in [Springshare LibCal](https://springshare.com/libcal/).

[See this application in use](https://lib.bgsu.edu/book/) for the Jerome Library at Bowling Green State University.

## Getting Started
This package is developed for [PHP 7.4 and above](https://www.php.net/supported-versions.php). The application including required dependencies may be downloaded from the [GitHub Releases](https://github.com/BGSU-LITS/book/releases) for the project.

Alternatively, the source code for the project may be downloaded or cloned from [GitHub](https://github.com/BGSU-LITS/book). If you do so, you'll need to then install the required dependencies via [Composer](https://getcomposer.org/) as so:
```
php composer.phar install --no-dev
```

### Adding to a web server
Make sure that the directory containing the application is placed outside of the publicly served files of your web server. To make the application available to end users, you should only make the `public/` directory accessible. For example, using a symbolic link to place the application into a folder named `book` in your `htdocs` directory:
```
cd /path/to/htdocs
ln -s /path/to/book/public book
```

Consult your web server documentation for other options.

### File permissions
Cache files are stored within the `cache/` directory. This directory must have the necessary write permissions so PHP is able to create and edit files within.

## Configuration
Configuration is specified in the `settings.php` file. To create this file, copy the example version:
```
cp settings.php.example settings.php
```

Below, we'll describe the settings that should be configured before using the application. Some settings are disabled by being commented out. You should make sure that any setting you use is not commented out.

### Logging
You should specify a path to store error logs made by the application:
```php
$settings['framework']->log = '/path/to/error.log';
```

### Sessions
You must specify a Base64 encoded, 32-bit session key to use this application:
```php
$settings['session']->key = 'key';
```

Note that the string `'key'` above is not a valid example. Among other means, you may use the `openssl` command to generate a valid value:
```
openssl rand -base64 32
```

### LibCal
The following settings must be made to communicate with the LibCal API:

#### Host
The host for the client will be the hostname of your LibCal instance, i.e. `<institution>.libcal.com`. For example, we'll use BGSU's host of `bgsu.libcal.com`:
```php
$settings['libcal']->host = 'bgsu.libcal.com';
```

#### Client ID and Secret
Visit the Dashboard of your LibCal instance, and navigate to Admin > API. Choose the API Authentication tab, and use the Create New Application button under the Applications section. You may specify any Application Name and Description you prefer and leave checked any of the scopes you want to access with the client.

Once you've created the application, you will need to copy the values from the Client Id and Client Secret columns of the Applications table. For example, we'll use a Client ID of `100` and a Client Secret of `61483dcf150d97c921abbe1f8024eb2e`:
```php
$settings['libcal']->clientId = '100';
$settings['libcal']->clientSecret = '61483dcf150d97c921abbe1f8024eb2e';
```

## Customization
### Settings
Certain settings that are configured within LibCal are not available through the API. To work around that issue, the below settings can be configured for Locations, Categories or Spaces. This is accomplished by creating a new `MetaConfig` object with the ID of the Location, Category or Space:
```php
$id = 100; // An example ID.
$meta = new MetaConfig($id);
```

Then, you may configure any of the following settings:
```php
// Set the required email domain(s), similar to LibCal configuration.
$meta->emailDomain = '@example.edu';

// Set the default length of a booking as a duration.
$meta->lengthDefault = Duration::create('2 hours');

// Set the minimum length of a booking as a duration.
$meta->lengthMinimum = Duration::create('1 hour');

// Set the maximum length of a booking as a duration.
$meta->lengthMaximum = Duration::create('3 hours');
```

For more information about durations, see the [documentation from the Period package](https://period.thephpleague.com/4.0/duration/).

Finally, add the `MetaConfig` object to the appropriate setting:
```php
// Add the configuration to a location.
$settings['book']->locations[] = $meta;

// Or, add the configuration to a category.
$settings['book']->categories[] = $meta;

// Or, add the configuration to an item.
$settings['book']->items[] = $meta;
```

### Templates
The templates used by the application are written in [Twig](https://twig.symfony.com/) and are available in two locations.

#### `vendor/bgsu-lits/template/`
This directory has `page.html.twig` which specifies a layout for all pages, as well as `error.html.twig` which is used when the application must display an error.

#### `template/`
This directory has an `action/` directory with templates for each action the application takes.

You may edit these templates, but it is recommended instead to copy the files first to your own directory, and then configure the `settings.php` file to use your templates first if they are available:
```php
$settings['template']->paths[] = '/path/to/your/templates';
```

There are also other template settings which are primarily used by the `page.html.twig` file, as demonstrated below:
```php
// Name of the site.
$settings['template']->site = 'Book';

// HTML contact information for the site.
$settings['template']->contact =
    '<p>For assistance, <a href="https://www.bgsu.edu/library/ask-us/">' .
    'please contact the University Libraries</a>.</p>';

// Menu for the site.
$settings['template']->menu = [
    [
        'text' => 'Appointments',
        'href' => 'https://bgsu.libcal.com/appointments/ira',
    ],
    [
        'text' => 'Study Spaces',
        'href' => '/book/study-spaces',
    ],
    [
        'text' => 'Library Hours',
        'href' => 'https://www.bgsu.edu/library/about/library-hours/',
    ],
];
```

### Dealing with the cache
Templates are compiled into the `cache/` directory. For your changes to appear, you must clear the compiled versions. One way to accomplish this is via the Composer `reset-cache` command:
```
php composer.phar reset-cache
```

Alternative, you may enable the debug mode in `settings.php` to recompile templates after they have changed:
```php
$settings['framework']->debug = true;
```

## Upgrading
If you installed the application via a release, make sure to back up your `settings.php` file, as well as anything else you changed, before replacing the application with a new release. You'll also need to take care to either keep your `cache/` directory, or reapply the necessary permissions.

If you installed the application via source code, you will need to obtain a new copy, for example:
```
git pull
```

Then you'll need to update the package dependencies:
```
php composer.phar install --no-dev
```

Note: this will also remove the files in the `cache/` directory.


## Related Projects
[A LibCal API client](https://github.com/bgsu-lits/libcal) used by this application is also available from the BGSU University Libraries.

The IUPUI University Library provides an example [LibCal Room Reservation Application](https://github.com/iupui-university-library/libcal_rooms) that includes their own implementation of a client.

## Development
This application was developed by the [Library Information Technology Services](https://github.com/BGSU-LITS) of the [University Libraries at Bowling Green Sate University](https://www.bgsu.edu/library/). The code is licensed under the [MIT License](LICENSE). Issues may be reported to the [GitHub Project](https://github.com/BGSU-LITS/book).

Contributions are also welcome, preferably via pull requests. Before submitting changes, please be insure to install development dependencies and run `test` command via Composer (`php composer.phar test`) to check that code conforms to the project standards. Additionally, templates can also be checked via the `twigcs` command via Composer (`php composer.phar twigcs`).
