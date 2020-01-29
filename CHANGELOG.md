# Release Notes

## [Unreleased](https://github.com/laravel/passport/compare/v8.3.1...8.x)


## [v8.3.1 (2020-01-29)](https://github.com/laravel/passport/compare/v8.3.0...v8.3.1)

### Fixed
- Remove foreign keys ([20e9b66](https://github.com/laravel/passport/commit/20e9b66fcd003ba41301fc5de23b9892e307051a))


## [v8.3.0 (2020-01-28)](https://github.com/laravel/passport/compare/v8.2.0...v8.3.0)

### Added
- Add a Passport Client factory to Passport publishing ([#1171](https://github.com/laravel/passport/pull/1171))

### Changed
- Use bigIncrements and indexes on relationships ([#1169](https://github.com/laravel/passport/pull/1169), [140a693](https://github.com/laravel/passport/commit/140a693a079f5611b3342360cde00b10e94162c1))


## [v8.2.0 (2020-01-07)](https://github.com/laravel/passport/compare/v8.1.0...v8.2.0)

### Added
- Update ClientCommand to support public clients ([#1151](https://github.com/laravel/passport/pull/1151))
- Purge Command for revoked and/or expired tokens and auth codes ([#1159](https://github.com/laravel/passport/pull/1159), [6c1ea42](https://github.com/laravel/passport/commit/6c1ea42e66100b15ecad89b0e1c5ccaa12b4331b))

### Changed
- Replace deprecated package and namespaces ([#1158](https://github.com/laravel/passport/pull/1158))


## [v8.1.0 (2019-12-30)](https://github.com/laravel/passport/compare/v8.0.2...v8.1.0)

### Added
- Allow access to HTTP response status code on OAuthServerException ([#1148](https://github.com/laravel/passport/pull/1148))
- Modify UserRepository to check for 'findAndValidateForPassport' method ([#1144](https://github.com/laravel/passport/pull/1144))


## [v8.0.2 (2019-11-26)](https://github.com/laravel/passport/compare/v8.0.1...v8.0.2)

### Changed
- Add abstract CheckCredentials middleware and allows to create ([#1127](https://github.com/laravel/passport/pull/1127))


## [v8.0.1 (2019-11-19)](https://github.com/laravel/passport/compare/v8.0.0...v8.0.1)

### Fixed
- Fix `actingAsClient` testing method ([#1119](https://github.com/laravel/passport/pull/1119))


## [v8.0.0 (2019-10-29)](https://github.com/laravel/passport/compare/v7.5.1...v8.0.0)

### Added
- Add ability to customize the `RefreshToken` ([#966](https://github.com/laravel/passport/pull/966))
- Add support for "public" clients ([#1065](https://github.com/laravel/passport/pull/1065))

### Changed
- Rework HandlesOAuthErrors trait to middleware ([#937](https://github.com/laravel/passport/pull/937))
- Use a renderable exception for OAuth errors ([#1066](https://github.com/laravel/passport/pull/1066))
- Use diactoros 2.0 and psr-http-factory ([aadf603](https://github.com/laravel/passport/commit/aadf603c1f45cfa4bbf954bfc3abc30cdd572683))
- Replaced helpers with Blade directives ([#939](https://github.com/laravel/passport/pull/939))
- Use caret for constraints ([d906804](https://github.com/laravel/passport/commit/d906804c2faccca0333801eccfbf6c2fa5afbaee))
- Dropped support for Laravel 5.8 ([654cc09](https://github.com/laravel/passport/commit/654cc09b06b600c5629497aa2567be44c285d113))
- Dropped support for PHP 7.1 ([3c830ac](https://github.com/laravel/passport/commit/3c830accaa1feefdeda0038b3d684cf4c80a0c52))
- Upgrade to league/oauth2-server 8.0 ([97e3026](https://github.com/laravel/passport/commit/97e3026790d953d7a67fe487e30775cd995e93df))

### Fixed
- Fix exception will thrown if token belongs to first party clients ([#1040](https://github.com/laravel/passport/pull/1040))
- Fix auth codes table customization ([#1044](https://github.com/laravel/passport/pull/1044))
- Add key type to refresh token model ([e400c2b](https://github.com/laravel/passport/commit/e400c2b665f66b5669e792e42b6d1479cff23df7))


## [v7.5.1 (2019-10-08)](https://github.com/laravel/passport/compare/v7.5.0...v7.5.1)

### Fixed
- Cast returned client identifier value to string ([#1091](https://github.com/laravel/passport/pull/1091))


## [v7.5.0 (2019-09-24)](https://github.com/laravel/passport/compare/v7.4.1...v7.5.0)

### Added
- Add `actingAsClient` method for tests ([#1083](https://github.com/laravel/passport/pull/1083))


## [v7.4.1 (2019-09-10)](https://github.com/laravel/passport/compare/v7.4.0...v7.4.1)

### Fixed
- Fixed key types for models ([#1078](https://github.com/laravel/passport/pull/1078), [a9a885d3](https://github.com/laravel/passport/commit/a9a885d3c2344ec133ed42a0268e503a76810982))


## [v7.4.0 (2019-08-20)](https://github.com/laravel/passport/compare/v7.3.5...v7.4.0)

### Added
- Let Passport support inherited parent scopes ([#1068](https://github.com/laravel/passport/pull/1068))
- Accept requests with the encrypted X-XSRF-TOKEN HTTP header ([#1069](https://github.com/laravel/passport/pull/1069))


## [v7.3.5 (2019-08-06)](https://github.com/laravel/passport/compare/v7.3.4...v7.3.5)

### Fixed
- Use `bigInteger` column type for `user_id` columns ([#1057](https://github.com/laravel/passport/pull/1057))


## [v7.3.4 (2019-07-30)](https://github.com/laravel/passport/compare/v7.3.3...v7.3.4)

### Changed
- Remove old 5.9 constraints ([58eb99c](https://github.com/laravel/passport/commit/58eb99cac0668ba61f3c9dc03694848f0ac7035a))


## [v7.3.3 (2019-07-29)](https://github.com/laravel/passport/compare/v7.3.2...v7.3.3)

### Changed
- Update version constraints for Laravel 6.0 ([609b5e8](https://github.com/laravel/passport/commit/609b5e829bf65dbeffb83dc8c324275fe0ebf30c))


## [v7.3.2 (2019-07-11)](https://github.com/laravel/passport/compare/v7.3.1...v7.3.2)

### Fixed
- Merge default Passport configuration ([#1039](https://github.com/laravel/passport/pull/1039), [e260c86](https://github.com/laravel/passport/commit/e260c865c218f00e4ad0c445dc45852e254d60c7))


## [v7.3.1 (2019-07-02)](https://github.com/laravel/passport/compare/v7.3.0...v7.3.1)

### Changed
- Change server property type in `CheckClientCredentialForAnyScope` ([#1034](https://github.com/laravel/passport/pull/1034))


## [v7.3.0 (2019-05-28)](https://github.com/laravel/passport/compare/v7.2.2...v7.3.0)

### Added
- Allow first party clients to skip the authorization prompt ([#1022](https://github.com/laravel/passport/pull/1022))

### Fixed
- Fix AccessToken docblock ([#996](https://github.com/laravel/passport/pull/996))


## [v7.2.2 (2019-03-13)](https://github.com/laravel/passport/compare/v7.2.1...v7.2.2)

### Fixed
- Allow installs of zend-diactoros 2 ([c0c3fca](https://github.com/laravel/passport/commit/c0c3fca80d8f5af90dcbf65e62bdd1abee9ac25d))


## [v7.2.1 (2019-03-12)](https://github.com/laravel/passport/compare/v7.2.0...v7.2.1)

### Fixed
- Change `wasRecentlyCreated` to `false` ([#979](https://github.com/laravel/passport/pull/979))


## [v7.2.0 (2019-02-14)](https://github.com/laravel/passport/compare/v7.1.0...v7.2.0)

### Changed
- Changed the way to get action path from `url()` to `route()` ([#950](https://github.com/laravel/passport/pull/950))
- Allow `'*'` scope to be used with Client Credentials ([#949](https://github.com/laravel/passport/pull/949))

### Fixed
- Replace `fire()` with `dispatch()` ([#952](https://github.com/laravel/passport/pull/952))


## [v7.1.0 (2019-01-22)](https://github.com/laravel/passport/compare/v7.0.5...v7.1.0)

### Added
- Added `redirect_uri` and `user_id` options to cli ([#921](https://github.com/laravel/passport/pull/921), [8b8570c](https://github.com/laravel/passport/commit/8b8570cc297ac7216d8f8caebb78a1e916093458))
- Add `ext-json` dependency ([#940](https://github.com/laravel/passport/pull/940))

### Changed
- Make name an optional question ([#926](https://github.com/laravel/passport/pull/926))

### Fixed
- Do not auto increment `AuthCode` ID ([#929](https://github.com/laravel/passport/pull/929))
- Allow multiple redirects when creating clients ([#928](https://github.com/laravel/passport/pull/928))
- Add responses for destroy methods ([#942](https://github.com/laravel/passport/pull/942))


## [v7.0.5 (2019-01-02)](https://github.com/laravel/passport/compare/v7.0.4...v7.0.5)

### Fixed
- Rename property ([#920](https://github.com/laravel/passport/pull/920))


## [v7.0.4 (2018-12-31)](https://github.com/laravel/passport/compare/v7.0.3...v7.0.4)

### Added
- Add middleware CheckClientCredentialsForAnyScope ([#855](https://github.com/laravel/passport/pull/855))
- Support a default scope when no scope was requested by the client ([#879](https://github.com/laravel/passport/pull/879))
- Allow setting expiration of personal access tokens ([#919](https://github.com/laravel/passport/pull/919))

### Changed
- Change auth code table to the model's table ([#865](https://github.com/laravel/passport/pull/865))
- Made whereRevoked consistent ([#868](https://github.com/laravel/passport/pull/868))
- Use unsignedInteger column type for `client_id` columns ([47f0021](https://github.com/laravel/passport/commit/47f00212c2f9b26ef6b90444facb8d8178b7dae6))

### Fixed
- Prevent passing empty string variable to retrieveById method ([#861](https://github.com/laravel/passport/pull/861)) 


## [v7.0.3 (2018-10-22)](https://github.com/laravel/passport/compare/v7.0.2...v7.0.3)

### Added
- Add names to routes for re-usability ([#846](https://github.com/laravel/passport/pull/846))
- Add user relationship to client model ([#851](https://github.com/laravel/passport/pull/851), [3213be8](https://github.com/laravel/passport/commit/3213be8c7c449037d1e5507f9b5ef1fb3ddb16a2))
- Add the ability to retrieve current client ([#854](https://github.com/laravel/passport/pull/854))

### Fixed
- Fix migrations tag publish ([#832](https://github.com/laravel/passport/pull/832))


## [v7.0.2 (2018-09-25)](https://github.com/laravel/passport/compare/v7.0.1...v7.0.2)

### Changed
- `Authcode` model is now used for persisting new authcodes ([#808](https://github.com/laravel/passport/pull/808))
- `resources/assets` directory was flattened ([#813](https://github.com/laravel/passport/pull/813))

### Fixed
- Personal client exception ([#831](https://github.com/laravel/passport/pull/831), [7bb53d1](https://github.com/laravel/passport/commit/7bb53d1ae4f8f375cc9461d232053958740002da))


## [v7.0.1 (2018-08-13)](https://github.com/laravel/passport/compare/v7.0.0...v7.0.1)

### Added
- Add option to enable cookie serialization ([9012496](https://github.com/laravel/passport/commit/90124969cdd4ff39d4cd5a608c23bbe16e772f7e))


## [v7.0.0 (2018-08-13)](https://github.com/laravel/passport/compare/v6.0.7...v7.0.0)

### Changed
- Don't serialize by default ([29e9d53](https://github.com/laravel/passport/commit/29e9d5312f3b11381f1fd472bde1fbbd73122cf1))
