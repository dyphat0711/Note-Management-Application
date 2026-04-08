/**
 * @jest-environment jsdom
 */

const Security = require('../frontend/js/security.js');

describe('Security.escapeHTML', () => {
  test('should escape HTML special characters', () => {
    expect(Security.escapeHTML('<script>alert("XSS")</script>'))
      .toBe('&lt;script&gt;alert(&quot;XSS&quot;)&lt;&#x2F;script&gt;');
  });

  test('should escape ampersand', () => {
    expect(Security.escapeHTML('Tom & Jerry')).toBe('Tom &amp; Jerry');
  });

  test('should escape single and double quotes', () => {
    expect(Security.escapeHTML("It's a \"test\"")).toBe('It&#39;s a &quot;test&quot;');
  });

  test('should handle empty string', () => {
    expect(Security.escapeHTML('')).toBe('');
  });

  test('should handle null and undefined', () => {
    expect(Security.escapeHTML(null)).toBe('');
    expect(Security.escapeHTML(undefined)).toBe('');
  });

  test('should handle numbers', () => {
    expect(Security.escapeHTML(123)).toBe('123');
    expect(Security.escapeHTML(0)).toBe('0');
  });

  test('should escape backticks and equals', () => {
    expect(Security.escapeHTML('`test`=value')).toBe('&#x60;test&#x60;&#x3D;value');
  });
});

describe('Security.isValidEmail', () => {
  test('should validate correct email formats', () => {
    expect(Security.isValidEmail('user@example.com')).toBe(true);
    expect(Security.isValidEmail('test.user@domain.co.uk')).toBe(true);
    expect(Security.isValidEmail('name+tag@gmail.com')).toBe(true);
  });

  test('should reject invalid email formats', () => {
    expect(Security.isValidEmail('invalid')).toBe(false);
    expect(Security.isValidEmail('missing@domain')).toBe(false);
    expect(Security.isValidEmail('@nodomain.com')).toBe(false);
    expect(Security.isValidEmail('spaces @domain.com')).toBe(false);
  });

  test('should handle empty input', () => {
    expect(Security.isValidEmail('')).toBe(false);
  });
});

describe('Security.validatePassword', () => {
  test('should accept valid passwords', () => {
    expect(Security.validatePassword('password123')).toEqual({ valid: true, message: '' });
    expect(Security.validatePassword('Secure1234')).toEqual({ valid: true, message: '' });
  });

  test('should reject passwords shorter than 8 characters', () => {
    expect(Security.validatePassword('pass1')).toEqual({ 
      valid: false, 
      message: 'Password must be at least 8 characters long.' 
    });
  });

  test('should reject passwords without letters', () => {
    expect(Security.validatePassword('12345678')).toEqual({ 
      valid: false, 
      message: 'Password must contain at least one letter.' 
    });
  });

  test('should reject passwords without numbers', () => {
    expect(Security.validatePassword('password')).toEqual({ 
      valid: false, 
      message: 'Password must contain at least one number.' 
    });
  });

  test('should handle empty password', () => {
    expect(Security.validatePassword('')).toEqual({ 
      valid: false, 
      message: 'Password must be at least 8 characters long.' 
    });
  });
});

describe('Security.sanitizeInput', () => {
  test('should remove null bytes', () => {
    expect(Security.sanitizeInput('test\0input')).toBe('testinput');
  });

  test('should trim whitespace', () => {
    expect(Security.sanitizeInput('  trimmed  ')).toBe('trimmed');
  });

  test('should handle null and undefined', () => {
    expect(Security.sanitizeInput(null)).toBe('');
    expect(Security.sanitizeInput(undefined)).toBe('');
  });

  test('should preserve normal input', () => {
    expect(Security.sanitizeInput('normal text')).toBe('normal text');
  });
});

describe('Security.validateNoteTitle', () => {
  test('should accept valid titles', () => {
    expect(Security.validateNoteTitle('My Note Title')).toEqual({ valid: true, message: '' });
  });

  test('should reject empty titles', () => {
    expect(Security.validateNoteTitle('')).toEqual({ valid: false, message: 'Title is required.' });
  });

  test('should reject titles longer than 200 characters', () => {
    const longTitle = 'a'.repeat(201);
    expect(Security.validateNoteTitle(longTitle)).toEqual({ 
      valid: false, 
      message: 'Title must be less than 200 characters.' 
    });
  });

  test('should sanitize input before validation', () => {
    expect(Security.validateNoteTitle('  valid title  ')).toEqual({ valid: true, message: '' });
  });
});

describe('Security.validateNoteContent', () => {
  test('should accept valid content', () => {
    expect(Security.validateNoteContent('Some note content')).toEqual({ valid: true, message: '' });
  });

  test('should allow empty content', () => {
    expect(Security.validateNoteContent('')).toEqual({ valid: true, message: '' });
    expect(Security.validateNoteContent(null)).toEqual({ valid: true, message: '' });
  });

  test('should reject content longer than 50000 characters', () => {
    const longContent = 'a'.repeat(50001);
    expect(Security.validateNoteContent(longContent)).toEqual({ 
      valid: false, 
      message: 'Content must be less than 50,000 characters.' 
    });
  });
});

describe('Security token management', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  describe('Security.storeToken', () => {
    test('should store valid token in localStorage', () => {
      Security.storeToken('test.token.here');
      expect(localStorage.getItem('token')).toBe('test.token.here');
    });

    test('should not store invalid tokens', () => {
      Security.storeToken(null);
      Security.storeToken(undefined);
      Security.storeToken(123);
      expect(localStorage.getItem('token')).toBeNull();
    });
  });

  describe('Security.getToken', () => {
    test('should retrieve stored token', () => {
      localStorage.setItem('token', 'valid.jwt.token');
      expect(Security.getToken()).toBe('valid.jwt.token');
    });

    test('should return null for non-existent token', () => {
      expect(Security.getToken()).toBeNull();
    });

    test('should return null for invalid JWT format', () => {
      localStorage.setItem('token', 'not-a-jwt');
      expect(Security.getToken()).toBeNull();
    });

    test('should return null for non-string token', () => {
      localStorage.setItem('token', JSON.stringify({ token: 'fake' }));
      expect(Security.getToken()).toBeNull();
    });
  });

  describe('Security.clearAuth', () => {
    test('should remove token and user from localStorage', () => {
      localStorage.setItem('token', 'test.token');
      localStorage.setItem('user', JSON.stringify({ id: 1 }));
      
      Security.clearAuth();
      
      expect(localStorage.getItem('token')).toBeNull();
      expect(localStorage.getItem('user')).toBeNull();
    });

    test('should clear sessionStorage', () => {
      sessionStorage.setItem('temp', 'data');
      Security.clearAuth();
      expect(sessionStorage.length).toBe(0);
    });
  });
});

describe('Security.getUser', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  test('should return parsed user object', () => {
    const user = { id: 1, name: 'Test User', email: 'test@example.com' };
    localStorage.setItem('user', JSON.stringify(user));
    
    expect(Security.getUser()).toEqual(user);
  });

  test('should return null when no user stored', () => {
    expect(Security.getUser()).toBeNull();
  });

  test('should return null on parse error', () => {
    localStorage.setItem('user', 'invalid json');
    expect(Security.getUser()).toBeNull();
  });
});

describe('Security.createSafeElement', () => {
  test('should create DOM element from HTML string', () => {
    const html = '<div class="test">Content</div>';
    const fragment = Security.createSafeElement(html);
    
    expect(fragment).toBeDefined();
    expect(fragment.querySelector('.test').textContent).toBe('Content');
  });

  test('should handle empty HTML', () => {
    const fragment = Security.createSafeElement('');
    expect(fragment.children.length).toBe(0);
  });
});
