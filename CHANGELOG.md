# Release Notes

## [Unreleased](https://github.com/laravel/passport/compare/v10.4.1...10.x)

## [v10.4.1](https://github.com/laravel/passport/compare/v10.4.0...v10.4.1) - 2022-04-16

### Changed

- Add new URI Rule to validate URI and use it to RedirectRule. by @victorbalssa in https://github.com/laravel/passport/pull/1544

## [v10.4.0](https://github.com/laravel/passport/compare/v10.3.3...v10.4.0) - 2022-03-30

### Changed

- Upgrade firebase/php-jwt to ^6.0 by @prufrock in https://github.com/laravel/passport/pull/1538

## [v10.3.3](https://github.com/laravel/passport/compare/v10.3.2...v10.3.3) - 2022-03-08

### Changed

- Use anonymous migrations by @mmachatschek in https://github.com/laravel/passport/pull/1531

## [v10.3.2](https://github.com/laravel/passport/compare/v10.3.1...v10.3.2) - 2022-02-22

### Fixed

- Fix Faker deprecations by @X-Coder264 in https://github.com/laravel/passport/pull/1530

## [v10.3.1 (2022-01-25)](https://github.com/laravel/passport/compare/v10.3.0...v10.3.1)

### Changed

- Allow to use custom authorization server response ([#1521](https://github.com/laravel/passport/pull/1521))

## [v10.3.0 (2022-01-12)](https://github.com/laravel/passport/compare/v10.2.2...v10.3.0)

### Changed

- Laravel 9 Support ([#1516](https://github.com/laravel/passport/pull/1516))

## [v10.2.2 (2021-12-07)](https://github.com/laravel/passport/compare/v10.2.1...v10.2.2)

### Fixed

- Fix jsonSerialize PHP 8.1 issue ([#1512](https://github.com/laravel/passport/pull/1512))

## [v10.2.1 (2021-12-07)](https://github.com/laravel/passport/compare/v10.2.0...v10.2.1)

### Fixed

- Fix `str_replace` error when third parameter ($subject) is null ([#1511](https://github.com/laravel/passport/pull/1511))

## [v10.2.0 (2021-11-02)](https://github.com/laravel/passport/compare/v10.1.4...v10.2.0)

### Added

- Add custom encryption key for JWT tokens ([#1501](https://github.com/laravel/passport/pull/1501))

### Changed

- Refactor expiry dates to intervals ([#1500](https://github.com/laravel/passport/pull/1500))

## [v10.1.4 (2021-10-19)](https://github.com/laravel/passport/compare/v10.1.3...v10.1.4)

### Fixed

- Ensure client model factory always creates models with a primary key ([#1492](https://github.com/laravel/passport/pull/1492)

## [v10.1.3 (2021-04-06)](https://github.com/laravel/passport/compare/v10.1.2...v10.1.3)

### Changed

- Use app helper ([3d1e6bb](https://github.com/laravel/passport/commit/3d1e6bbdedf71efb147f3b5205259e8b20c2e6ad))

### Fixed

- Fix binding ([e3478de](https://github.com/laravel/passport/commit/e3478dedd938671b7598239cc8554f77de9ab9c7))

## [v10.1.2 (2021-03-02)](https://github.com/laravel/passport/compare/v10.1.1...v10.1.2)

### Fixed

- Backport phpseclib v2 ([#1418](https://github.com/laravel/passport/pull/1418))

## [v10.1.1 (2021-02-23)](https://github.com/laravel/passport/compare/v10.1.0...v10.1.1)

### Changed

- Update to phpseclib v3 ([#1410](https://github.com/laravel/passport/pull/1410))

## [v10.1.0 (2020-11-26)](https://github.com/laravel/passport/compare/v10.0.1...v10.1.0)

### Added

- PHP 8 Support ([#1373](https://github.com/laravel/passport/pull/1373))

### Removed

- Remove Vue components ([#1352](https://github.com/laravel/passport/pull/1352))

## [v10.0.1 (2020-09-15)](https://github.com/laravel/passport/compare/v10.0.0...v10.0.1)

### Fixed

- Use newFactory to properly reference factory ([#1349](https://github.com/laravel/passport/pull/1349))

## [v10.0.0 (2020-09-08)](https://github.com/laravel/passport/compare/v9.3.2...v10.0.0)

### Added

- Support Laravel 8 & drop PHP 7.2 support ([#1336](https://github.com/laravel/passport/pull/1336))

### Changed

- `forceFill` new auth code attributes ([#1266](https://github.com/laravel/passport/pull/1266))
- Use only one PSR 7 implementation ([#1330](https://github.com/laravel/passport/pull/1330))

### Removed

- Remove old static personal client methods ([#1325](https://github.com/laravel/passport/pull/1325))
- Remove Guzzle dependency ([#1327](https://github.com/laravel/passport/pull/1327))

## [v9.3.2 (2020-07-27)](https://github.com/laravel/passport/compare/v9.3.1...v9.3.2)

### Fixes

- Fix cookie handling for security release ([#1322](https://github.com/laravel/passport/pull/1322), [75f1ad2](https://github.com/laravel/passport/commit/75f1ad218ddf4500f2beb9e5c2fb186530e8ddb6))

## [v9.3.1 (2020-07-21)](https://github.com/laravel/passport/compare/v9.3.0...v9.3.1)

### Fixed

- Use custom models in purge command if set ([#1316](https://github.com/laravel/passport/pull/1316))
- Apply table responsive on table class ([#1318](https://github.com/laravel/passport/pull/1318))

## [v9.3.0 (2020-06-30)](https://github.com/laravel/passport/compare/v9.2.2...v9.3.0)

### Added

- Guzzle 7 support ([#1311](https://github.com/laravel/passport/pull/1311))

## [v9.2.2 (2020-06-25)](https://github.com/laravel/passport/compare/v9.2.1...v9.2.2)

### Fixed

- Fix maxlength for token names ([#1300](https://github.com/laravel/passport/pull/1300))
- Improve `passport:install` command ([#1294](https://github.com/laravel/passport/pull/1294))

## [v9.2.1 (2020-05-14)](https://github.com/laravel/passport/compare/v9.2.0...v9.2.1)

### Fixed

- Fix actingAsClient token relation ([#1268](https://github.com/laravel/passport/pull/1268))
- Fix HashCommand ([bedf02c](https://github.com/laravel/passport/commit/bedf02c8bb8fb9ca373e34f0ceefb2e8c5bf006b))

## [v9.2.0 (2020-05-12](https://github.com/laravel/passport/compare/v9.1.0...v9.2.0)

### Added

- Allow to change Models database connection ([#1255](https://github.com/laravel/passport/pull/1255), [7ab3bdb](https://github.com/laravel/passport/commit/7ab3bdbdb9bf162f2da9d8c445523dc63c862248))

### Fixed

- Nonstandard ID in the token's relationship with the user ([#1267](https://github.com/laravel/passport/pull/1267))

## [v9.1.0 (2020-05-08](https://github.com/laravel/passport/compare/v9.0.1...v9.1.0)

### Added

- Implement secret modal ([#1258](https://github.com/laravel/passport/pull/1258))
- Warn about one-time-hashed-secret ([#1259](https://github.com/laravel/passport/pull/1259))
- Add force option to hash command ([#1251](https://github.com/laravel/passport/pull/1251))

### Fixed

- Implement personal access client config ([#1260](https://github.com/laravel/passport/pull/1260))

## [v9.0.1 (2020-05-06)](https://github.com/laravel/passport/compare/v9.0.0...v9.0.1)

### Fixed

- Fix displaying secret in Vue component ([#1244](https://github.com/laravel/passport/pull/1244))
- Moved provider check to bearer token only ([#1246](https://github.com/laravel/passport/pull/1246))
- Fix create client call ([aff9d09](https://github.com/laravel/passport/commit/aff9d0933737354d04df98cfc431fa20309be03a))

## [v9.0.0 (2020-05-05)](https://github.com/laravel/passport/compare/v8.5.0...v9.0.0)

### Added

- Allow client credentials secret to be hashed ([#1145](https://github.com/laravel/passport/pull/1145), [ccbcfeb](https://github.com/laravel/passport/commit/ccbcfeb5301e8f757395ba0e43980615acf4385e), [1c40ae0](https://github.com/laravel/passport/commit/1c40ae07503aeb23173d48f3a6e5757cafcfd71b))
- Implement `passport:hash` command ([#1238](https://github.com/laravel/passport/pull/1238))
- Initial support for multiple providers ([#1220](https://github.com/laravel/passport/pull/1220))

### Changed

- Client credentials middleware should allow any valid client ([#1132](https://github.com/laravel/passport/pull/1132))
- Switch from `getKey()` to `getAuthIdentifier()` to match Laravel core ([#1134](https://github.com/laravel/passport/pull/1134))
- Use Hasher interface instead of HashManager ([#1157](https://github.com/laravel/passport/pull/1157))
- Bump league server dependency ([#1237](https://github.com/laravel/passport/pull/1237))

### Removed

- Remove deprecated functionality ([#1235](https://github.com/laravel/passport/pull/1235))
- Drop support for old JWT versions ([#1236](https://github.com/laravel/passport/pull/1236))

## [v8.5.0 (2020-05-05)](https://github.com/laravel/passport/compare/v8.4.4...v8.5.0)

### Added

- Automatic configuration of client UUIDs ([#1231](https://github.com/laravel/passport/pull/1231))

## [v8.4.4 (2020-04-21)](https://github.com/laravel/passport/compare/v8.4.3...v8.4.4)

### Fixed

- Fix 500 Internal Server Error response ([#1222](https://github.com/laravel/passport/pull/1222))

## [v8.4.3 (2020-03-31)](https://github.com/laravel/passport/compare/v8.4.2...v8.4.3)

### Fixed

- Fix resolveInheritedScopes ([#1207](https://github.com/laravel/passport/pull/1207))

## [v8.4.2 (2020-03-24)](https://github.com/laravel/passport/compare/v8.4.1...v8.4.2)

### Fixed

- `mergeConfigFrom` already checked if app is running with config cached ([#1205](https://github.com/laravel/passport/pull/1205))

## [v8.4.1 (2020-03-04)](https://github.com/laravel/passport/compare/v8.4.0...v8.4.1)

### Fixed

- Forget session keys on invalid match ([#1192](https://github.com/laravel/passport/pull/1192))
- Update dependencies for PSR request ([#1201](https://github.com/laravel/passport/pull/1201))

## [v8.4.0 (2020-02-12)](https://github.com/laravel/passport/compare/v8.3.1...v8.4.0)

### Changed

- Implement auth token for access requests ([#1188](https://github.com/laravel/passport/pull/1188))

### Fixed

- Revoke refresh tokens when auth tokens get revoked ([#1186](https://github.com/laravel/passport/pull/1186))

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
