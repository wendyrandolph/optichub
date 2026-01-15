// tailwind.config.cjs
module.exports = {
  darkMode: "class",
  content: ["./resources/**/*.blade.php", "./resources/**/*.js"],
  theme: {
    extend: {
      colors: {
        ui: {
          bg: "rgb(var(--ui-bg) / <alpha-value>)",
          surface: "rgb(var(--ui-surface) / <alpha-value>)",
          muted: "rgb(var(--ui-surface-muted) / <alpha-value>)",
          accent: "rgb(var(--ui-surface-accent) / <alpha-value>)",
          border: "rgb(var(--ui-border) / <alpha-value>)",
          text: "rgb(var(--ui-text) / <alpha-value>)",
          subtle: "rgb(var(--ui-text-subtle) / <alpha-value>)",
          primary: "rgb(var(--ui-primary) / <alpha-value>)",
          primaryHover: "rgb(var(--ui-primary-hover) / <alpha-value>)",
          accent: "rgb(var(--ui-accent) / <alpha-value>)",
          success: "rgb(var(--ui-success) / <alpha-value>)",
          danger: "rgb(var(--ui-danger) / <alpha-value>)",
          primaryTint: "rgb(var(--ui-primary-tint) / <alpha-value>)",
          accentTint: "rgb(var(--ui-accent-tint) / <alpha-value>)",
          lavenderTint: "rgb(var(--ui-lavender-tint) / <alpha-value>)",
          primaryEdge: "rgb(var(--ui-primary-edge) / <alpha-value>)",
          accentEdge: "rgb(var(--ui-accent-edge) / <alpha-value>)",
          lavenderEdge: "rgb(var(--ui-lavender-edge) / <alpha-value>)",
        },
        tenant: {
          primary: "rgb(var(--tenant-primary) / <alpha-value>)",
          secondary: "rgb(var(--tenant-secondary) / <alpha-value>)",
          neutral: "rgb(var(--tenant-neutral) / <alpha-value>)",
          onPrimary: "rgb(var(--tenant-on-primary) / <alpha-value>)",
        },
        // Legacy aliases (to be removed)
        bg: "rgb(var(--ui-bg) / <alpha-value>)",
        surface: "rgb(var(--ui-surface) / <alpha-value>)",
        "surface-muted": "rgb(var(--ui-surface-muted) / <alpha-value>)",
        border: "rgb(var(--ui-border) / <alpha-value>)",
        text: "rgb(var(--ui-text) / <alpha-value>)",
        "text-subtle": "rgb(var(--ui-text-subtle) / <alpha-value>)",
        "brand-primary": "rgb(var(--ui-primary) / <alpha-value>)",
        "brand-secondary": "rgb(var(--ui-primary-hover) / <alpha-value>)",
        "brand-accent": "rgb(var(--ui-accent) / <alpha-value>)",
        success: "rgb(var(--ui-success) / <alpha-value>)",
        danger: "rgb(var(--ui-danger) / <alpha-value>)",
      },
      boxShadow: {
        card: "var(--card-shadow)",
      },
      borderRadius: {
        card: "var(--border-radius-card)",
        sm: "var(--border-radius-small)",
      },
    },
  },
  plugins: [],
};
