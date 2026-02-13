import React from 'react';
import {useCurrentFrame, useVideoConfig, spring, interpolate} from 'remotion';
import {COLORS} from '../theme';

export const Logo: React.FC<{
  size?: number;
  showBadge?: boolean;
  delay?: number;
}> = ({size = 48, showBadge = true, delay = 0}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({
    frame,
    fps,
    delay,
    config: {damping: 200},
  });

  const badgeAppear = spring({
    frame,
    fps,
    delay: delay + 15,
    config: {damping: 200},
  });

  const scale = interpolate(appear, [0, 1], [0.8, 1]);
  const opacity = interpolate(appear, [0, 1], [0, 1]);

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: size * 0.3,
        transform: `scale(${scale})`,
        opacity,
      }}
    >
      {/* Icon */}
      <div
        style={{
          width: size,
          height: size,
          borderRadius: size * 0.25,
          background: `linear-gradient(135deg, ${COLORS.emerald[500]}, ${COLORS.emerald[600]})`,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          boxShadow: `0 0 ${size * 0.6}px ${COLORS.emerald[500]}44`,
        }}
      >
        <svg
          width={size * 0.6}
          height={size * 0.6}
          viewBox="0 0 24 24"
          fill="none"
        >
          <path
            d="M12 2L2 7v10l10 5 10-5V7L12 2z"
            stroke="white"
            strokeWidth="1.5"
            fill="none"
          />
          <path d="M12 11v6M9 14h6" stroke="white" strokeWidth="2" strokeLinecap="round" />
        </svg>
      </div>

      {/* Text */}
      <div style={{display: 'flex', flexDirection: 'column'}}>
        <span
          style={{
            fontSize: size * 0.55,
            fontWeight: 700,
            color: COLORS.white,
            letterSpacing: '-0.02em',
            lineHeight: 1,
          }}
        >
          PostVisit
          <span style={{color: COLORS.emerald[400]}}>.ai</span>
        </span>
      </div>

      {/* Opus 4.6 badge */}
      {showBadge && (
        <div
          style={{
            opacity: badgeAppear,
            transform: `translateY(${interpolate(badgeAppear, [0, 1], [8, 0])}px)`,
            background: `linear-gradient(135deg, ${COLORS.emerald[600]}33, ${COLORS.emerald[500]}22)`,
            border: `1px solid ${COLORS.emerald[500]}44`,
            borderRadius: 20,
            padding: `${size * 0.08}px ${size * 0.2}px`,
            display: 'flex',
            alignItems: 'center',
            gap: 6,
          }}
        >
          <div
            style={{
              width: 6,
              height: 6,
              borderRadius: '50%',
              backgroundColor: COLORS.emerald[400],
            }}
          />
          <span
            style={{
              fontSize: size * 0.22,
              color: COLORS.emerald[300],
              fontWeight: 500,
            }}
          >
            Opus 4.6
          </span>
        </div>
      )}
    </div>
  );
};
