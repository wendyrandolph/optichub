module.exports = {
  plugins: {
    'postcss-import': {},   // 👈 must come before tailwindcss
    tailwindcss: {},
    autoprefixer: {},
  },
};
