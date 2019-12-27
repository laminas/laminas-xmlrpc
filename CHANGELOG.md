# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.9.0 - 2019-12-27

### Added

- Nothing.

### Changed

- [#40](https://github.com/zendframework/zend-xmlrpc/pull/40) modifies detection of integer values on 64-bit systems. Previously, i8 values parsed by the client were always cast to BigInteger values. Now, on 64-bit systems, they are cast to integers.

Disables use of BigInteger for XMLRPC i8 type if host machine is 64-bit.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.8.0 - 2019-10-19

### Added

- [#38](https://github.com/zendframework/zend-xmlrpc/pull/38) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#38](https://github.com/zendframework/zend-xmlrpc/pull/38) removes support for zend-stdlib v2 releases.

### Fixed

- Nothing.

## 2.7.0 - 2018-05-14

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#32](https://github.com/zendframework/zend-xmlrpc/pull/32) removes support for HHVM.

### Fixed

- Nothing.

## 2.6.2 - 2018-01-25

### Added

- [#29](https://github.com/zendframework/zend-xmlrpc/pull/29) adds support for
  PHP 7.2, by replacing deprecated `list`/`each` syntax with a functional
  equivalent.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.1 - 2017-08-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#27](https://github.com/zendframework/zend-xmlrpc/pull/27) fixed a memory leak
  caused by repetitive addition of `Accept` and `Content-Type` headers on subsequent
  HTTP requests produced by the `Zend\XmlRpc\Client`.

## 2.6.0 - 2016-06-21

### Added

- [#19](https://github.com/zendframework/zend-xmlrpc/pull/19) adds support for
  zend-math v3.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.2 - 2016-04-21

### Added

- [#11](https://github.com/zendframework/zend-xmlrpc/pull/11),
  [#12](https://github.com/zendframework/zend-xmlrpc/pull/12),
  [#13](https://github.com/zendframework/zend-xmlrpc/pull/13),
  [#14](https://github.com/zendframework/zend-xmlrpc/pull/14),
  [#15](https://github.com/zendframework/zend-xmlrpc/pull/15), and
  [#16](https://github.com/zendframework/zend-xmlrpc/pull/16)
  added and prepared the documentation for publication at
  https://zendframework.github.io/zend-xmlrpc/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#17](https://github.com/zendframework/zend-xmlrpc/pull/17) updates
  dependencies to allow zend-stdlib v3 releases.
