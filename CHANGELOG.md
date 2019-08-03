# Release Notes

## [Unreleased](https://github.com/laravel/passport/compare/v7.3.4...master)

### Added
- Add ability to customize the `RefreshToken` ([#966](https://github.com/laravel/passport/pull/966))

### Changed
- Rework HandlesOAuthErrors trait to middleware ([#937](https://github.com/laravel/passport/pull/937))
- Use diactoros 2.0 and psr-http-factory ([aadf603](https://github.com/laravel/passport/commit/aadf603c1f45cfa4bbf954bfc3abc30cdd572683))
- Replaced helpers with Blade directives ([#939](https://github.com/laravel/passport/pull/939))
- Use caret for constraints ([d906804](https://github.com/laravel/passport/commit/d906804c2faccca0333801eccfbf6c2fa5afbaee))


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
