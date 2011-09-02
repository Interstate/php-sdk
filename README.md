Interstate PHP SDK
==================

This is the new base SDK for working with the [Interstate API v2](http://developers.interstateapp.com/v2).

## Getting Started
To get started with the new sdk, include Interstate.php in your app and initialize an instance of the Interstate class, with your OAuth app's settings.

```php
<?php

$instance = new Interstate( array(

	'client_id'		=> 'CLIENT_ID',
	'client_secret'	=> 'CLIENT_SECRET',
	'redirect_uri'	=> 'REDIRECT_URI'
	//'oauth_token	=> 'OAUTH_TOKEN',
	//'https'		=> false

));
```
Now you can call any method on the `$instance` object. The `$instance` object has the following methods:

* `getApiUrl()`
* `getRootUrl()`
* `getAuthorizeUrl()`
* `sign( $uri )`
* `setAccessToken( $oauth_token )`
* `getAccessToken( $code, $type = 'authorization_code', $setToken = true )`
* `fetch( $params, $post = array() )`

## Authentication

To authenticate a user, first redirect them to the Interstate OAuth Authorization page. You can get this URL by calling the `getAuthorizeUrl()` method, for example:

```php
<?php

header( 'Location: ' . $instance->getAuthorizeUrl() );
```

If the user approves your authorization request, they will be sent back to your app, with a query string parameter with the key `code`. Grab this code and then run the `getAccessToken()` method to convert the code in to an `oauth_token` and a `refresh_token`.

```php
<?php

$tokens = $instance->getAccessToken( $_GET[ 'code' ] ); // { oauth_token: '..', refresh_token: '..' }
```

## Refresh Tokens

Each oauth_token only lasts for a max of 3600 seconds (1 hour), so when your `oauth_token` expires, you will need to generate a new one, by using the refresh_token you received when you ran `getAccessToken()`. To use your `refresh_token` to generate a new `oauth_token`, use the `getAccessToken()` method again, like so:

```php
<?php

$newTokens = $instance->getAccessToken( $refresh_token, 'refresh_token' );
```

When you call this method, you will be returned with both a new `oauth_token` and a new `refresh_token`. When the new `oauth_token` expires in 3600 seconds, you simply repeat the process.

## Making requests

Once you've authenticated with a user or manually set an `oauth_token` (either in the Interstate `__construct` or by calling `setAccessToken()`), you are able to make authenticated requests to the Interstate API. You can make custom requests by using the `fetch()` method, like so:

```php
<?php

$response = $instance->fetch( '/roadmap/4e2e8e88e962317e16000000' );
```

If you're not trying to make a `GET` request (e.g. a `DELETE` request), simply either specify in the URL parameter or pass an array instead.

```php
<?php

$response = $instance->fetch( 'DELETE /roadmap/4e2e8e88e962317e16000000' );
$response = $instance->fetch( array(
	
	'url'	=> 'roadmap/4e2e8e88e962317e16000000',
	'verb'	=> 'DELETE'

));
```

If you need to post data, you can pass an array of key => values as second argument to the method, or pass a single array as the only argument with a `post` key.

```php
<?php

$response = $instance->fetch( 'PUT /roadmap/4e2e8e88e962317e16000000', array(
	
	'title' => 'New title'
	
));

$response = $instance->fetch( array(
	
	'url'	=> '/roadmap/4e2e8e88e962317e16000000', 
	'verb'	=> 'PUT',
	'post'	=> array(
		
		'title' => 'New title'
		
	)

);
```