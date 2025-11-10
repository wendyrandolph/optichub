/** @type {import('tailwindcss').Config} */
const colors = require('tailwindcss/colors');

module.exports = {
  darkMode: 'class',
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    // keep your semantic colors here
    colors: {
      ...colors,
      brand: {
        primary: 'rgb(var(--brand-primary) / <alpha-value>)',
        secondary: 'rgb(var(--brand-secondary) / <alpha-value>)',
      },
      surface: {
        page: 'rgb(var(--page-bg) / <alpha-value>)',
        card: 'rgb(var(--card-bg) / <alpha-value>)',
        accent: 'rgb(var(--card-accent-bg) / <alpha-value>)',
      },
      text: {
        base: 'rgb(var(--text-base) / <alpha-value>)',
        subtle: 'rgb(var(--text-subtle) / <alpha-value>)',
      },
      border: {
        default: 'rgb(var(--border-default) / <alpha-value>)',
      },
      status: {
        danger: '#DC3545',
        warning: '#FFC107',
        'calendar-danger': '#CC2936',
      },
      blue: {
        50: '#F7FAFD',
        100: '#E7EDF5',
        300: '#B7D0EB',
        500: '#679CD5',
        700: '#2E69A8',
        800: '#4D6B8A',
        900: '#2E5D95',
      },
      green: {
        50: '#F0FBE8',
        200: '#D0F7BA',
        600: '#6BA23E',
        700: '#78D147',
        900: '#62AC39',
        ink: '#2E7D32',
      },
      purple: {
        50: '#F7F0FE',
        100: '#E6DAF6',
        300: '#CCB6E7',
        500: '#A586CB',
        700: '#7D5DA7',
        900: '#5E4587',
      },
      'cws-dark': '#2E5D95',
      'cws-mid': '#2E69A8',
      'cws-light': '#679CD5',
      'green-ink': '#2E7D32',
      ink: {
        0: '#FFFFFF',
        50: '#F7FAFD',
        100: '#EBEFF4',
        300: '#D0D6DF',
        500: '#8A95A8',
        700: '#444D5A',
        900: '#1C212B',
      },
    },
    extend: {
      boxShadow: {
        card: 'var(--card-shadow)',
        'card-light': 'var(--card-shadow-light)',
      },
      borderRadius: {
        card: 'var(--border-radius-card)',
        'card-sm': 'var(--border-radius-card-small)',
        sm: 'var(--border-radius-small)',
      },
      colors: {
        optic: {
          bg: 'rgb(var(--page-bg) / <alpha-value>)',
          panel: 'rgb(var(--card-accent-bg) / <alpha-value>)',
          card: 'rgb(var(--card-bg) / <alpha-value>)',
          text: 'rgb(var(--text-base) / <alpha-value>)',
          muted: 'rgb(var(--text-subtle) / <alpha-value>)',
          border: 'rgb(var(--border-default) / <alpha-value>)',
          primary: 'rgb(var(--brand-primary) / <alpha-value>)',
          secondary: 'rgb(var(--brand-secondary) / <alpha-value>)',
          brand: 'rgb(var(--brand-primary) / <alpha-value>)',
          brandAccent: 'rgb(var(--brand-secondary) / <alpha-value>)',
          surface: 'rgb(var(--page-bg) / <alpha-value>)',
          card: 'rgb(var(--card-bg) / <alpha-value>)',
          text: 'rgb(var(--text-base) / <alpha-value>)',
          subtle: 'rgb(var(--text-subtle) / <alpha-value>)',
          border: 'rgb(var(--border-default) / <alpha-value>)',
        },
        heading: 'var(--text-heading)',
        body: 'var(--text-body)',
        muted: 'var(--text-muted)',
      },
      screens: {
        atTablet: '768px',
        atMaxTablet: { max: '900px' },
        atMaxMedium: { max: '1024px' },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/line-clamp'),
  ],
};
