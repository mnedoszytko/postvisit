import React from 'react';
import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  spring,
  interpolate,
  Sequence,
} from 'remotion';
import {COLORS, FONT} from '../theme';
import {Background} from '../components/Background';
import {Logo} from '../components/Logo';
import {TypewriterText} from '../components/AnimatedText';

const FEATURE_PILLS = [
  'Reverse AI Scribe',
  'Contextual Health Chat',
  'Smart Health Record',
] as const;

/** Animated pill that fades in with a spring scale */
const FeaturePill: React.FC<{
  text: string;
  delay: number;
}> = ({text, delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const scale = interpolate(appear, [0, 1], [0.85, 1]);

  return (
    <div
      style={{
        opacity: appear,
        transform: `scale(${scale})`,
        padding: '14px 32px',
        borderRadius: 40,
        background: `linear-gradient(135deg, ${COLORS.emerald[600]}33, ${COLORS.emerald[500]}18)`,
        border: `1px solid ${COLORS.emerald[500]}55`,
        backdropFilter: 'blur(8px)',
      }}
    >
      <span
        style={{
          fontSize: 22,
          fontWeight: 600,
          color: COLORS.emerald[300],
          fontFamily: FONT.body,
          letterSpacing: '0.01em',
        }}
      >
        {text}
      </span>
    </div>
  );
};

/**
 * VIDEO 1: PostVisit.ai Hero Introduction (10 seconds / 300 frames @ 30fps)
 *
 * A cinematic brand intro with logo, tagline, feature pills, and closing scale-up.
 */
export const HeroIntro: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // --- Grid fade in (0-1s) ---
  const gridOpacity = interpolate(frame, [0, fps * 1], [0, 0.6], {
    extrapolateRight: 'clamp',
  });

  // --- Subtitle (6-8s) ---
  const subtitleDelay = fps * 6;
  const subtitleAppear = spring({
    frame,
    fps,
    delay: subtitleDelay,
    config: {damping: 200},
  });

  // --- Final scale-up and glow (8-10s) ---
  const outroProgress = interpolate(frame, [fps * 8, fps * 10], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const outroScale = interpolate(outroProgress, [0, 1], [1, 1.04]);
  const outroGlow = interpolate(outroProgress, [0, 1], [0, 0.25]);

  return (
    <AbsoluteFill>
      <Background variant="radial" />

      {/* Extra grid overlay with animated opacity */}
      <div
        style={{
          position: 'absolute',
          inset: 0,
          backgroundImage: `linear-gradient(${COLORS.emerald[500]}06 1px, transparent 1px), linear-gradient(90deg, ${COLORS.emerald[500]}06 1px, transparent 1px)`,
          backgroundSize: '80px 80px',
          opacity: gridOpacity,
        }}
      />

      {/* Intensifying emerald glow during outro */}
      <div
        style={{
          position: 'absolute',
          top: '30%',
          left: '40%',
          width: 700,
          height: 700,
          borderRadius: '50%',
          background: `radial-gradient(circle, ${COLORS.emerald[500]} 0%, transparent 70%)`,
          opacity: outroGlow,
          filter: 'blur(100px)',
          pointerEvents: 'none',
        }}
      />

      {/* Main content wrapper with outro scale */}
      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          transform: `scale(${outroScale})`,
          gap: 40,
        }}
      >
        {/* Logo (1-2.5s): delay = 1s = 30 frames */}
        <Sequence from={0} premountFor={fps * 1}>
          <div style={{display: 'flex', justifyContent: 'center'}}>
            <Logo size={72} showBadge={true} delay={fps * 1} />
          </div>
        </Sequence>

        {/* Tagline typewriter (2.5-4.5s) */}
        <Sequence from={0} premountFor={Math.round(fps * 2.5)}>
          <div
            style={{
              display: 'flex',
              justifyContent: 'center',
              marginTop: 8,
            }}
          >
            <TypewriterText
              text="Your AI companion after every doctor visit"
              fontSize={36}
              color={COLORS.slate[300]}
              delay={Math.round(fps * 2.5)}
              speed={2}
            />
          </div>
        </Sequence>

        {/* Feature pills (4.5-6s) */}
        <Sequence from={0} premountFor={Math.round(fps * 4.5)}>
          <div
            style={{
              display: 'flex',
              gap: 20,
              justifyContent: 'center',
              marginTop: 16,
            }}
          >
            {FEATURE_PILLS.map((pill, i) => (
              <FeaturePill
                key={pill}
                text={pill}
                delay={Math.round(fps * 4.5) + i * 8}
              />
            ))}
          </div>
        </Sequence>

        {/* Subtitle about Claude (6-8s) */}
        <Sequence from={0} premountFor={subtitleDelay}>
          <div
            style={{
              opacity: subtitleAppear,
              transform: `translateY(${interpolate(subtitleAppear, [0, 1], [16, 0])}px)`,
              textAlign: 'center',
              maxWidth: 800,
              marginTop: 12,
            }}
          >
            <span
              style={{
                fontSize: 20,
                fontWeight: 400,
                color: COLORS.slate[400],
                fontFamily: FONT.body,
                lineHeight: 1.6,
              }}
            >
              Built with{' '}
              <span style={{color: COLORS.emerald[400], fontWeight: 600}}>
                Claude Opus 4.6
              </span>{' '}
              — the most capable AI model for clinical reasoning
            </span>
          </div>
        </Sequence>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
