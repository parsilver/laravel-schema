/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/js/**/*.{js,jsx,ts,tsx}',
    './resources/views/**/*.blade.php'
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        schema: {
          dark: '#1a1a2e',
          sidebar: '#16213e',
          accent: '#4f46e5'
        }
      }
    }
  },
  plugins: []
}
