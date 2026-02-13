import React from 'react';
import {AbsoluteFill, useCurrentFrame, useVideoConfig, interpolate} from 'remotion';
import {COLORS, GRADIENTS} from '../theme';

export const Background: React.FC<{
  variant?: 'dark' | 'radial' | 'emerald';
}> = ({variant = 'dark'}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const glowOpacity = interpolate(
    Math.sin(frame / (fps * 2)),
    [-1, 1],
    [0.03, 0.08],
  );

  const bg =
    variant === 'radial'
      ? GRADIENTS.darkRadial
      : variant === 'emerald'
        ? GRADIENTS.emeraldDark
        : `linear-gradient(180deg, ${COLORS.slate[950]} 0%, ${COLORS.slate[900]} 100%)`;

  return (
    <AbsoluteFill style={{background: bg}}>
      {/* Subtle grid */}
      <div
        style={{
          position: 'absolute',
          inset: 0,
          backgroundImage: `linear-gradient(${COLORS.emerald[500]}08 1px, transparent 1px), linear-gradient(90deg, ${COLORS.emerald[500]}08 1px, transparent 1px)`,
          backgroundSize: '60px 60px',
        }}
      />
      {/* Glow orb */}
      <div
        style={{
          position: 'absolute',
          top: '20%',
          left: '30%',
          width: 600,
          height: 600,
          borderRadius: '50%',
          background: `radial-gradient(circle, ${COLORS.emerald[500]} 0%, transparent 70%)`,
          opacity: glowOpacity,
          filter: 'blur(80px)',
        }}
      />
    </AbsoluteFill>
  );
};
