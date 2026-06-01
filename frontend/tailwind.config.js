/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#534AB7',
          50: '#EEEDF9',
          100: '#D5D3F2',
          200: '#ABA7E5',
          300: '#817BD8',
          400: '#574FCB',
          500: '#534AB7',
          600: '#3F3796',
          700: '#2E2870',
          800: '#1E1A4A',
          900: '#0F0D25',
        },
        blue: {
          fleet: '#185FA5',
        },
        teal: {
          fleet: '#0F6E56',
        },
        amber: {
          fleet: '#854F0B',
        },
        danger: '#A32D2D',
        dark: '#2C2C2A',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      spacing: {
        '18': '4.5rem',
        '72': '18rem',
        '84': '21rem',
        '96': '24rem',
      },
    },
  },
  plugins: [],
}
