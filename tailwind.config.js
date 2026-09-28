/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './wp-content/themes/horse-racing-paper/**/*.php',
    './wp-content/plugins/horse-racing-paper/**/*.php',
    './wp-content/plugins/horse-racing-paper/**/*.js',
    './wp-content/themes/horse-racing-paper/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        ink: {
          DEFAULT: '#0f1419',
          soft: '#151b22',
          mid: '#1a222c',
          line: '#2a3440',
          edge: '#334155',
        },
        paper: {
          DEFAULT: '#f3f6fa',
          soft: '#e4ebf3',
          dim: '#b7c4d4',
          mute: '#b7c4d4',
        },
        brass: {
          DEFAULT: '#d4b26a',
          dim: '#241f14',
        },
        signal: {
          hot: '#ffb4a8',
          watch: '#ffe08a',
          place: '#9fd4ff',
          low: '#b7c4d4',
        },
      },
      fontFamily: {
        display: ['"Noto Serif TC"', '"Source Han Serif TC"', 'serif'],
        body: ['"Noto Sans TC"', '"Source Han Sans TC"', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      maxWidth: {
        shell: '1280px',
      },
    },
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  },
};
