module.exports = [
  {
    languageOptions: {
      ecmaVersion: 2020,
      sourceType: "script",
      globals: {
        // Browser globals
        window: "readonly",
        document: "readonly",
        console: "readonly",
        // jQuery
        jQuery: "readonly",
        $: "readonly",
        // Base64 from 64.js
        Base64: "readonly",
      }
    },
    rules: {
      // Relax some rules for legacy code
      "no-unused-vars": "warn",
      "no-undef": "warn",
    }
  }
];
