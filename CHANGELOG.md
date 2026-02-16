# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Laravel Pint for code formatting
- Unit tests for TripRouteGenerator
- Feature tests for Trip management  
- Security headers middleware (X-Frame-Options, CSP, etc.)
- Rate limiting on API routes
- One-click installer (`install.bat`)
- Auto server starter (`start_server.bat`)
- Comprehensive README with badges and features
- Android APK build guide
- Quick APK build reference
- Professional assessment documentation

### Changed
- Updated INSTALLATION.md with focus on 1-click installation
- Improved TripRouteGenerator to use DB transactions
- Changed default ORS profile to `driving-car`
- Enhanced store grouping logic in TripAssignmentService

### Fixed
- GI-based trip sequence assignment bug
- Route generator return leg calculation
- Store coordinate matching issues
- Sequence NULL problem in route generation

## [1.0.0] - 2026-02-16

### Added
- Initial release
- Route optimization with OpenRouteService
- GI (Goods Issue) integration
- Multi-GI trip assignment
- Admin & Driver panels with Filament
- Real-time GPS tracking
- Geofencing (100m radius)
- Interactive maps with Leaflet.js
- Excel import/export
- Mobile app support (Android via Capacitor)
- Background location tracking
- Trip status management
- Store coordinate management

### Security
- Added basic authentication
- Environment variable protection
- CSRF protection
