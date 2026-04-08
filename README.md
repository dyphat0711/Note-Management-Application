# NoteFlow - Note Management Application

A secure, modern note-taking application built with vanilla JavaScript, featuring user authentication, rich text notes, labels, dark mode, and collaborative sharing capabilities.

## Features

- 🔐 **User Authentication**: Register, login, password reset with OTP
- 📝 **Note Management**: Create, edit, delete, and organize notes
- 🏷️ **Labels**: Categorize notes with custom color-coded labels
- 🔒 **Security**: XSS protection, input validation, secure JWT token handling
- 🌙 **Dark Mode**: Toggle between light and dark themes
- 📱 **Responsive Design**: Works on desktop and mobile devices
- 💾 **Auto-save**: Notes are automatically saved as you type
- 🔖 **Pinning & Locking**: Pin important notes and lock sensitive ones with passwords

## Prerequisites

Before running this application, ensure you have:

- **Node.js** (v14 or higher) installed
- **npm** (comes with Node.js)
- A web browser (Chrome, Firefox, Safari, or Edge)

## Installation

1. Clone or navigate to the project directory:
   ```bash
   cd /workspace
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

## Running the Application

### Frontend

Open the application in your browser by opening the `frontend/index.html` file directly, or serve it using a local web server:

**Option 1: Using Python**
```bash
# Python 3
python3 -m http.server 8000

# Python 2
python -m SimpleHTTPServer 8000
```

**Option 2: Using Node.js (http-server)**
```bash
npx http-server frontend -p 8000
```

Then open your browser and navigate to `http://localhost:8000`.

### Backend

The backend API is located in `backend/api/`. You'll need a PHP server and MySQL database to run the backend. See the backend documentation for setup instructions.

## Running Unit Tests

This project uses **Jest** as the testing framework with **jsdom** for DOM simulation.

### Run All Tests

To run all unit tests:

```bash
npm test
```

Or with watch mode for development:

```bash
npm test -- --watch
```

### Test Coverage

To generate a coverage report:

```bash
npm test -- --coverage
```

### Run Specific Test Files

To run a specific test file:

```bash
npm test -- tests/security.test.js
```

### Test Structure

Tests are located in the `tests/` directory. Currently available tests:

- `tests/security.test.js` - Tests for security utilities including:
  - HTML escaping (XSS prevention)
  - Email validation
  - Password strength validation
  - Input sanitization
  - Note title/content validation
  - JWT token storage/retrieval
  - Authentication management
  - Safe DOM element creation

### Writing New Tests

1. Create a new test file in the `tests/` directory with the pattern `*.test.js`
2. Use the `@jest-environment jsdom` comment at the top for DOM-related tests
3. Import the module you want to test
4. Write test cases using Jest's `describe`, `test`, and `expect` functions

Example:
```javascript
/**
 * @jest-environment jsdom
 */

const MyModule = require('../frontend/js/myModule.js');

describe('MyModule.functionName', () => {
  test('should do something', () => {
    expect(MyModule.functionName()).toBe('expected result');
  });
});
```

## Project Structure

```
/workspace
├── frontend/
│   ├── index.html          # Main HTML file
│   ├── css/                # Stylesheets
│   └── js/
│       ├── app.js          # Main application logic
│       └── security.js     # Security utilities
├── backend/
│   ├── api/                # PHP API endpoints
│   ├── config/             # Database configuration
│   └── database.sql        # Database schema
├── tests/
│   └── security.test.js    # Unit tests for security module
├── package.json            # Project dependencies and scripts
├── babel.config.json       # Babel configuration for Jest
└── README.md               # This file
```

## Security Features

The application includes several security measures:

1. **HTML Escaping**: All user-generated content is escaped before rendering to prevent XSS attacks
2. **Input Validation**: Email, password, and note inputs are validated client-side
3. **Token Management**: JWT tokens are securely stored and validated
4. **Input Sanitization**: Null bytes and dangerous characters are removed from inputs

## Development

### Available npm Scripts

| Command | Description |
|---------|-------------|
| `npm test` | Run all unit tests |
| `npm test -- --watch` | Run tests in watch mode |
| `npm test -- --coverage` | Run tests with coverage report |
| `npm test -- --verbose` | Run tests with detailed output |

## Troubleshooting

### Tests Not Running

1. Ensure all dependencies are installed: `npm install`
2. Check that Node.js version is compatible (v14+)
3. Clear Jest cache: `npx jest --clearCache`

### Application Not Loading

1. Verify the backend server is running
2. Check browser console for errors
3. Ensure CORS is properly configured if running frontend and backend on different ports

## License

ISC

## Contributing

Contributions are welcome! Please ensure all new features include appropriate unit tests.
