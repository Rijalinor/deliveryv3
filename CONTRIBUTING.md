# Contributing to DeliveryV3

Thank you for considering contributing to DeliveryV3! We welcome contributions from the community.

## Code of Conduct

Please be respectful and constructive in your interactions.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce**
- **Expected vs actual behavior**
- **Screenshots** if applicable
- **Environment details** (OS, PHP version, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, include:

- **Clear title and description**
- **Use case** - why is this enhancement needed?
- **Possible implementation** (optional)

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Run tests**: `php artisan test`
5. **Run code formatting**: `./vendor/bin/pint`
6. **Commit**: `git commit -m 'Add amazing feature'`
7. **Push**: `git push origin feature/amazing-feature`
8. **Open a Pull Request**

### Coding Standards

- Follow **PSR-12** coding standard
- Use **Laravel Pint** for code formatting
- Write **meaningful commit messages**
- Add **tests** for new features
- Update **documentation** as needed

### Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=TripRouteGeneratorTest

# Run with coverage
php artisan test --coverage
```

### Code Formatting

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

## Development Workflow

1. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Setup environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Run migrations**:
   ```bash
   php artisan migrate
   ```

4. **Start development servers**:
   ```bash
   php artisan serve
   php artisan queue:work
   npm run dev
   ```

## Project Structure

```
app/
├── Filament/          # Admin & Driver panels
├── Models/            # Eloquent models
├── Services/          # Business logic
├── Http/
│   ├── Controllers/   # HTTP controllers
│   └── Middleware/    # HTTP middleware
tests/
├── Feature/           # Feature tests
└── Unit/             # Unit tests
```

## Questions?

Feel free to:
- Open an issue
- Ask in discussions
- Contact the maintainers

Thank you for your contribution! 🙏
