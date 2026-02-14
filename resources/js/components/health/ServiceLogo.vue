<template>
  <div
    class="flex items-center justify-center rounded-xl shrink-0"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
      backgroundColor: logoData?.bg || `${color}18`,
    }"
  >
    <svg
      v-if="logoData"
      :width="size * 0.55"
      :height="size * 0.55"
      :viewBox="logoData.viewBox"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        v-for="(p, i) in logoData.paths"
        :key="i"
        :d="p.d"
        :fill="p.mode === 'stroke' ? 'none' : (p.fill || color)"
        :stroke="p.mode === 'stroke' ? (p.fill || color) : 'none'"
        :stroke-width="p.strokeWidth || 0"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
    </svg>
    <span
      v-else
      class="font-bold leading-none select-none"
      :style="{ fontSize: `${size * 0.38}px`, color }"
    >
      {{ initials }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface LogoPath {
  d: string;
  fill?: string;
  mode?: 'fill' | 'stroke';
  strokeWidth?: number;
}

interface LogoData {
  viewBox: string;
  paths: LogoPath[];
  bg?: string;
}

const props = withDefaults(defineProps<{
  serviceId: string;
  color: string;
  size?: number;
}>(), {
  size: 44,
});

const initials = computed(() =>
  props.serviceId.charAt(0).toUpperCase()
);

const LOGOS: Record<string, LogoData> = {
  // ── Wearables ──────────────────────────────────────────────
  'apple-health': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.913 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701',
    }],
  },
  'google-fit': {
    viewBox: '0 0 24 24',
    bg: '#f3f4f6',
    paths: [
      { d: 'M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z', fill: '#4285F4' },
      { d: 'M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z', fill: '#34A853' },
      { d: 'M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z', fill: '#FBBC05' },
      { d: 'M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z', fill: '#EA4335' },
    ],
  },
  'fitbit': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 3.5m-2 0a2 2 0 1 0 4 0 2 2 0 1 0-4 0M12 10m-2 0a2 2 0 1 0 4 0 2 2 0 1 0-4 0M12 16.5m-2 0a2 2 0 1 0 4 0 2 2 0 1 0-4 0M5.5 10m-2 0a2 2 0 1 0 4 0 2 2 0 1 0-4 0M18.5 10m-2 0a2 2 0 1 0 4 0 2 2 0 1 0-4 0M12 23m-1.5 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 1 0-3 0M5.5 16.5m-1.5 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 1 0-3 0M18.5 16.5m-1.5 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 1 0-3 0M5.5 3.5m-1.5 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 1 0-3 0M18.5 3.5m-1.5 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 1 0-3 0',
    }],
  },
  'samsung-health': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
    }],
  },
  'garmin': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zm-.263 3.6A8.4 8.4 0 0120.4 12h-8.663z',
    }],
  },
  'withings': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M2 4L7 20L12 7L17 20L22 4',
      mode: 'stroke',
      strokeWidth: 2.5,
    }],
  },

  // ── EHR Portals (Health Networks) ──────────────────────────
  'rsb': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
      mode: 'stroke',
      strokeWidth: 1.5,
    }],
  },
  'rsw': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
      mode: 'stroke',
      strokeWidth: 1.5,
    }],
  },
  'cozo': {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101', mode: 'stroke', strokeWidth: 2 },
      { d: 'M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', mode: 'stroke', strokeWidth: 2 },
    ],
  },
  'ikp': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
      mode: 'stroke',
      strokeWidth: 1.5,
    }],
  },
  'epic-mychart': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 2C8.14 6 5 10 5 13.5 5 17.64 8.13 21 12 21s7-3.36 7-7.5C19 10 15.86 6 12 2z',
    }],
  },
  'cerner': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M19 8h-4V4c0-.55-.45-1-1-1h-4c-.55 0-1 .45-1 1v4H5c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h4v4c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-4h4c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1z',
    }],
  },

  // ── Pharmacies ─────────────────────────────────────────────
  'cvs': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
    }],
  },
  'walgreens': {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M3 10h18l-2 8c-.5 2-2.5 4-7 4s-6.5-2-7-4l-2-8z' },
      { d: 'M12 2L18 8L16.6 9.4L10.6 3.4Z' },
    ],
  },

  // ── Labs ───────────────────────────────────────────────────
  'quest': {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M9 4v12a3 3 0 006 0V4H9z' },
      { d: 'M7 1h10v3H7z' },
    ],
  },
  'labcorp': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 2l9 5v10l-9 5-9-5V7z',
    }],
  },

  // ── Insurance ──────────────────────────────────────────────
  'aetna': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
    }],
  },
  'united': {
    viewBox: '0 0 24 24',
    paths: [{
      d: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z',
    }],
  },
};

const logoData = computed(() => LOGOS[props.serviceId] || null);
</script>
