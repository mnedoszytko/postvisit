import React from 'react';
import {useCurrentFrame, useVideoConfig, spring, interpolate} from 'remotion';
import {COLORS} from '../theme';

/** A phone-shaped frame for showcasing mobile UI */
export const MockPhone: React.FC<{
  children: React.ReactNode;
  delay?: number;
  scale?: number;
}> = ({children, delay = 0, scale = 1}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const phoneScale = interpolate(appear, [0, 1], [0.9, 1]) * scale;
  const opacity = appear;

  const phoneWidth = 375;
  const phoneHeight = 812;

  return (
    <div
      style={{
        width: phoneWidth,
        height: phoneHeight,
        borderRadius: 40,
        border: `3px solid ${COLORS.slate[600]}`,
        background: COLORS.slate[900],
        overflow: 'hidden',
        transform: `scale(${phoneScale})`,
        opacity,
        boxShadow: `0 0 60px ${COLORS.emerald[500]}22, 0 20px 60px ${COLORS.slate[950]}88`,
        position: 'relative',
      }}
    >
      {/* Status bar */}
      <div
        style={{
          height: 44,
          background: COLORS.slate[900],
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '0 24px',
        }}
      >
        <div
          style={{
            width: 120,
            height: 28,
            borderRadius: 14,
            background: COLORS.slate[950],
          }}
        />
      </div>

      {/* Content */}
      <div style={{flex: 1, overflow: 'hidden', position: 'relative', height: phoneHeight - 44}}>
        {children}
      </div>
    </div>
  );
};

/** A browser-shaped frame for desktop UI */
export const MockBrowser: React.FC<{
  children: React.ReactNode;
  delay?: number;
  url?: string;
}> = ({children, delay = 0, url = 'postvisit.ai'}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const browserScale = interpolate(appear, [0, 1], [0.95, 1]);
  const opacity = appear;

  return (
    <div
      style={{
        width: 1200,
        height: 750,
        borderRadius: 12,
        border: `1px solid ${COLORS.slate[700]}`,
        background: COLORS.slate[900],
        overflow: 'hidden',
        transform: `scale(${browserScale})`,
        opacity,
        boxShadow: `0 0 80px ${COLORS.emerald[500]}11, 0 20px 60px ${COLORS.slate[950]}aa`,
      }}
    >
      {/* Title bar */}
      <div
        style={{
          height: 40,
          background: COLORS.slate[800],
          display: 'flex',
          alignItems: 'center',
          padding: '0 16px',
          gap: 8,
          borderBottom: `1px solid ${COLORS.slate[700]}`,
        }}
      >
        <div style={{display: 'flex', gap: 6}}>
          {['#ff5f57', '#febc2e', '#28c840'].map((c) => (
            <div
              key={c}
              style={{width: 12, height: 12, borderRadius: '50%', background: c}}
            />
          ))}
        </div>
        <div
          style={{
            flex: 1,
            height: 26,
            borderRadius: 6,
            background: COLORS.slate[700],
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <span style={{fontSize: 12, color: COLORS.slate[400]}}>{url}</span>
        </div>
      </div>

      {/* Content */}
      <div style={{height: 710, overflow: 'hidden', position: 'relative'}}>
        {children}
      </div>
    </div>
  );
};
