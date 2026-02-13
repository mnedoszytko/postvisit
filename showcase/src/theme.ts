export const COLORS = {
  emerald: {
    50: '#ecfdf5',
    100: '#d1fae5',
    200: '#a7f3d0',
    300: '#6ee7b7',
    400: '#34d399',
    500: '#10b981',
    600: '#059669',
    700: '#047857',
    800: '#065f46',
    900: '#064e3b',
  },
  slate: {
    50: '#f8fafc',
    100: '#f1f5f9',
    200: '#e2e8f0',
    300: '#cbd5e1',
    400: '#94a3b8',
    500: '#64748b',
    600: '#475569',
    700: '#334155',
    800: '#1e293b',
    900: '#0f172a',
    950: '#020617',
  },
  white: '#ffffff',
  amber: {
    400: '#fbbf24',
    500: '#f59e0b',
  },
  rose: {
    400: '#fb7185',
    500: '#f43f5e',
  },
  violet: {
    400: '#a78bfa',
    500: '#8b5cf6',
  },
  sky: {
    400: '#38bdf8',
    500: '#0ea5e9',
  },
} as const;

export const GRADIENTS = {
  emeraldDark: `linear-gradient(135deg, ${COLORS.slate[900]} 0%, ${COLORS.slate[800]} 50%, ${COLORS.emerald[900]} 100%)`,
  emeraldGlow: `linear-gradient(135deg, ${COLORS.emerald[600]} 0%, ${COLORS.emerald[400]} 100%)`,
  darkRadial: `radial-gradient(ellipse at 30% 20%, ${COLORS.emerald[900]}44 0%, ${COLORS.slate[900]} 70%)`,
  subtleGlow: `radial-gradient(circle at 50% 50%, ${COLORS.emerald[500]}22 0%, transparent 60%)`,
} as const;

export const FONT = {
  heading: 'Inter, system-ui, sans-serif',
  body: 'Inter, system-ui, sans-serif',
  mono: 'JetBrains Mono, monospace',
} as const;

export const VIDEO = {
  width: 1920,
  height: 1080,
  fps: 30,
} as const;
