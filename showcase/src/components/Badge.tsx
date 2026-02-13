import React from 'react';
import {useCurrentFrame, useVideoConfig, spring, interpolate} from 'remotion';
import {COLORS} from '../theme';

type BadgeVariant = 'emerald' | 'amber' | 'rose' | 'violet' | 'sky' | 'slate';

const VARIANT_COLORS: Record<BadgeVariant, {bg: string; border: string; text: string}> = {
  emerald: {bg: COLORS.emerald[500] + '22', border: COLORS.emerald[500] + '44', text: COLORS.emerald[300]},
  amber: {bg: COLORS.amber[500] + '22', border: COLORS.amber[500] + '44', text: COLORS.amber[400]},
  rose: {bg: COLORS.rose[500] + '22', border: COLORS.rose[500] + '44', text: COLORS.rose[400]},
  violet: {bg: COLORS.violet[500] + '22', border: COLORS.violet[500] + '44', text: COLORS.violet[400]},
  sky: {bg: COLORS.sky[500] + '22', border: COLORS.sky[500] + '44', text: COLORS.sky[400]},
  slate: {bg: COLORS.slate[700], border: COLORS.slate[600], text: COLORS.slate[300]},
};

export const Badge: React.FC<{
  text: string;
  variant?: BadgeVariant;
  delay?: number;
  icon?: string;
}> = ({text, variant = 'emerald', delay = 0, icon}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const colors = VARIANT_COLORS[variant];

  return (
    <div
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: 6,
        padding: '6px 14px',
        borderRadius: 20,
        background: colors.bg,
        border: `1px solid ${colors.border}`,
        opacity: appear,
        transform: `scale(${interpolate(appear, [0, 1], [0.8, 1])})`,
      }}
    >
      {icon && <span style={{fontSize: 14}}>{icon}</span>}
      <span style={{fontSize: 13, fontWeight: 500, color: colors.text}}>{text}</span>
    </div>
  );
};

/** Source attribution chip like in the chat */
export const SourceChip: React.FC<{
  label: string;
  variant: BadgeVariant;
  delay?: number;
}> = ({label, variant, delay = 0}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 15, stiffness: 200}});
  const colors = VARIANT_COLORS[variant];

  return (
    <div
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: 4,
        padding: '4px 10px',
        borderRadius: 12,
        background: colors.bg,
        border: `1px solid ${colors.border}`,
        opacity: appear,
        transform: `scale(${interpolate(appear, [0, 1], [0.5, 1])})`,
      }}
    >
      <div
        style={{
          width: 5,
          height: 5,
          borderRadius: '50%',
          background: colors.text,
        }}
      />
      <span style={{fontSize: 11, fontWeight: 500, color: colors.text}}>{label}</span>
    </div>
  );
};
