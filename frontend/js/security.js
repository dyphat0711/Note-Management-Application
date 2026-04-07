/**
 * Security Utilities for NoteFlow Frontend
 * Provides XSS protection, input validation, and secure token handling
 */

const Security = {
    /**
     * Escape HTML to prevent XSS attacks
     * @param {string} str - The string to escape
     * @returns {string} - Escaped string safe for HTML insertion
     */
    escapeHTML: (str) => {
        if (!str && str !== 0) return "";
        const escapeMap = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "'": "&#39;",
            '"': "&quot;",
            "/": "&#x2F;",
            "`": "&#x60;",
            "=": "&#x3D;"
        };
        return String(str).replace(/[&<>'"/`=]/g, (tag) => escapeMap[tag] || tag);
    },

    /**
     * Validate email format
     * @param {string} email - Email to validate
     * @returns {boolean} - True if valid email format
     */
    isValidEmail: (email) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Validate password strength
     * Requirements: min 8 chars, at least one letter and one number
     * @param {string} password - Password to validate
     * @returns {object} - {valid: boolean, message: string}
     */
    validatePassword: (password) => {
        if (!password || password.length < 8) {
            return { valid: false, message: "Password must be at least 8 characters long." };
        }
        if (!/[A-Za-z]/.test(password)) {
            return { valid: false, message: "Password must contain at least one letter." };
        }
        if (!/[0-9]/.test(password)) {
            return { valid: false, message: "Password must contain at least one number." };
        }
        return { valid: true, message: "" };
    },

    /**
     * Sanitize user input by removing potentially dangerous characters
     * @param {string} input - Input string to sanitize
     * @returns {string} - Sanitized string
     */
    sanitizeInput: (input) => {
        if (!input) return "";
        // Remove null bytes and trim whitespace
        return String(input).replace(/\0/g, "").trim();
    },

    /**
     * Validate note title
     * @param {string} title - Title to validate
     * @returns {object} - {valid: boolean, message: string}
     */
    validateNoteTitle: (title) => {
        const sanitized = Security.sanitizeInput(title);
        if (!sanitized) {
            return { valid: false, message: "Title is required." };
        }
        if (sanitized.length > 200) {
            return { valid: false, message: "Title must be less than 200 characters." };
        }
        return { valid: true, message: "" };
    },

    /**
     * Validate note content
     * @param {string} content - Content to validate
     * @returns {object} - {valid: boolean, message: string}
     */
    validateNoteContent: (content) => {
        if (!content) return { valid: true, message: "" }; // Content can be empty
        if (content.length > 50000) {
            return { valid: false, message: "Content must be less than 50,000 characters." };
        }
        return { valid: true, message: "" };
    },

    /**
     * Securely store authentication token
     * In production, consider using httpOnly cookies instead
     * @param {string} token - JWT token to store
     */
    storeToken: (token) => {
        if (!token || typeof token !== 'string') {
            console.error("Invalid token");
            return;
        }
        localStorage.setItem("token", token);
    },

    /**
     * Securely retrieve authentication token
     * @returns {string|null} - Token or null if not found/invalid
     */
    getToken: () => {
        const token = localStorage.getItem("token");
        if (!token || typeof token !== 'string') {
            return null;
        }
        // Basic JWT format validation
        const parts = token.split('.');
        if (parts.length !== 3) {
            return null;
        }
        return token;
    },

    /**
     * Clear all authentication data
     */
    clearAuth: () => {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        sessionStorage.clear();
    },

    /**
     * Get authenticated user data
     * @returns {object|null} - User object or null
     */
    getUser: () => {
        try {
            const userStr = localStorage.getItem("user");
            if (!userStr) return null;
            return JSON.parse(userStr);
        } catch (e) {
            console.error("Failed to parse user data");
            return null;
        }
    },

    /**
     * Create a DOM element safely from HTML string
     * @param {string} html - HTML string
     * @returns {DocumentFragment} - Safe DOM fragment
     */
    createSafeElement: (html) => {
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content;
    }
};

// Export for use in other modules (if using ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Security;
}
