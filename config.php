<?php
	// MySQL credentials
	// WARNING : these default parameters are automatically modified by Docker at build time
	// https://www.php.net/manual/en/pdo.construct.php
	const SQL_HOST = 'localhost';
	const SQL_DATABASE = 'source_web_console';
	const SQL_USERNAME = 'username';
	const SQL_PASSWORD = 'password';
	const SQL_CHARSET = 'utf8';
	const SQL_PORT = '3306';

	// SMTP credentials
	// https://github.com/PHPMailer/PHPMailer/blob/master/src/SMTP.php
	const SMTP_HOST = 'localhost';
	const SMTP_PORT = '25';
	const SMTP_USERNAME = '';
	const SMTP_PASSWORD = '';

	// DKIM settings
	// https://easydmarc.com/blog/how-to-configure-dkim-opendkim-with-postfix/
	const DKIM_DOMAIN = '';
	const DKIM_PRIVATE_KEY = '';
	const DKIM_SELECTOR = '';

	// OpenSSL settings
	// https://www.php.net/manual/en/ref.openssl.php
	const SSL_PHRASE = '';

	// Google Analytics
	// https://analytics.google.com/
	const ANALYTICS_TAG = '';

	// Google reCAPTCHA
	// https://developers.google.com/recaptcha/docs/v3
	const CAPTCHA_PUBLIC_KEY = '';
	const CAPTCHA_SECRET_KEY = '';
?>