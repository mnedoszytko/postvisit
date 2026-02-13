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
import {WordReveal} from '../components/AnimatedText';
import {Badge} from '../components/Badge';

/* ---------- sub-components ---------- */

/** Icon for each phase */
const PhaseIcon: React.FC<{
  phase: 'plan' | 'execute' | 'verify';
  active: boolean;
  completed: boolean;
  activeProgress: number;
}> = ({phase, active, completed, activeProgress}) => {
  const iconColor = completed
    ? COLORS.emerald[400]
    : active
      ? COLORS.emerald[300]
      : COLORS.slate[500];

  const icons: Record<string, React.ReactNode> = {
    plan: (
      <svg width={24} height={24} viewBox="0 0 24 24" fill="none">
        <circle cx={12} cy={12} r={9} stroke={iconColor} strokeWidth={2} />
        <path d="M12 7v5l3 3" stroke={iconColor} strokeWidth={2} strokeLinecap="round" />
      </svg>
    ),
    execute: (
      <svg width={24} height={24} viewBox="0 0 24 24" fill="none">
        <rect x={3} y={3} width={18} height={18} rx={3} stroke={iconColor} strokeWidth={2} />
        <path d="M8 12h8M12 8v8" stroke={iconColor} strokeWidth={2} strokeLinecap="round" />
      </svg>
    ),
    verify: (
      <svg width={24} height={24} viewBox="0 0 24 24" fill="none">
        <path
          d="M12 2L3 7v5c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z"
          stroke={iconColor}
          strokeWidth={2}
        />
        {completed && (
          <path
            d="M8 12l3 3 5-5"
            stroke={COLORS.emerald[400]}
            strokeWidth={2.5}
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        )}
      </svg>
    ),
  };

  return (
    <div
      style={{
        width: 48,
        height: 48,
        borderRadius: 24,
        background: active || completed
          ? `${COLORS.emerald[500]}22`
          : COLORS.slate[800],
        border: `2px solid ${
          completed ? COLORS.emerald[500] : active ? COLORS.emerald[500] + '88' : COLORS.slate[700]
        }`,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        boxShadow:
          active || completed
            ? `0 0 20px ${COLORS.emerald[500]}33`
            : 'none',
      }}
    >
      {completed ? (
        <svg width={24} height={24} viewBox="0 0 24 24" fill="none">
          <path
            d="M5 13l4 4L19 7"
            stroke={COLORS.emerald[400]}
            strokeWidth={3}
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      ) : (
        icons[phase]
      )}
    </div>
  );
};

/** A single phase box in the pipeline */
const PhaseBox: React.FC<{
  label: string;
  subtitle: string;
  phase: 'plan' | 'execute' | 'verify';
  delay: number;
  activeDelay: number;
  completeDelay: number;
}> = ({label, subtitle, phase, delay, activeDelay, completeDelay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});

  const activeProgress = interpolate(
    frame - activeDelay,
    [0, fps * 1.5],
    [0, 1],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  );

  const isActive = frame >= activeDelay && frame < completeDelay;
  const isCompleted = frame >= completeDelay;

  const boxBorder = isCompleted
    ? COLORS.emerald[500]
    : isActive
      ? COLORS.emerald[500] + '88'
      : COLORS.slate[700];

  const boxBg = isCompleted
    ? `${COLORS.emerald[500]}11`
    : isActive
      ? `${COLORS.emerald[500]}08`
      : COLORS.slate[800];

  const labelColor = isCompleted || isActive ? COLORS.white : COLORS.slate[400];

  // Pulse glow when active
  const pulseGlow = isActive
    ? interpolate(Math.sin((frame - activeDelay) * 0.15), [-1, 1], [0.15, 0.35])
    : 0;

  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 12,
        opacity: appear,
        transform: `translateY(${interpolate(appear, [0, 1], [20, 0])}px)`,
      }}
    >
      <PhaseIcon
        phase={phase}
        active={isActive}
        completed={isCompleted}
        activeProgress={activeProgress}
      />
      <div
        style={{
          width: 220,
          padding: '16px 20px',
          borderRadius: 12,
          background: boxBg,
          border: `1px solid ${boxBorder}`,
          textAlign: 'center',
          boxShadow: isActive
            ? `0 0 30px ${COLORS.emerald[500]}${Math.round(pulseGlow * 255)
                .toString(16)
                .padStart(2, '0')}`
            : isCompleted
              ? `0 0 20px ${COLORS.emerald[500]}22`
              : 'none',
        }}
      >
        <div
          style={{
            fontSize: 14,
            fontWeight: 700,
            color: labelColor,
            letterSpacing: '0.1em',
            textTransform: 'uppercase' as const,
            fontFamily: FONT.mono,
            marginBottom: 6,
          }}
        >
          {label}
        </div>
        <div style={{fontSize: 12, color: COLORS.slate[400], lineHeight: 1.4}}>
          {subtitle}
        </div>
      </div>
    </div>
  );
};

/** Connecting arrow between phases */
const ConnectingArrow: React.FC<{
  delay: number;
  activeDelay: number;
}> = ({delay, activeDelay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});

  const isActive = frame >= activeDelay;
  const lineColor = isActive ? COLORS.emerald[400] : COLORS.slate[600];

  const flowProgress = isActive
    ? interpolate(frame - activeDelay, [0, fps * 0.5], [0, 1], {
        extrapolateLeft: 'clamp',
        extrapolateRight: 'clamp',
      })
    : 0;

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        opacity: appear,
        width: 80,
        marginTop: 24,
        position: 'relative',
      }}
    >
      {/* Background line */}
      <div
        style={{
          width: '100%',
          height: 2,
          background: COLORS.slate[700],
          borderRadius: 1,
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        {/* Active flow line */}
        <div
          style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: `${flowProgress * 100}%`,
            height: '100%',
            background: lineColor,
            borderRadius: 1,
            boxShadow: isActive ? `0 0 6px ${COLORS.emerald[500]}66` : 'none',
          }}
        />
      </div>
      {/* Arrow head */}
      <div
        style={{
          width: 0,
          height: 0,
          borderTop: '5px solid transparent',
          borderBottom: '5px solid transparent',
          borderLeft: `8px solid ${flowProgress > 0.9 ? lineColor : COLORS.slate[700]}`,
          position: 'absolute',
          right: -4,
        }}
      />
    </div>
  );
};

/** Context layer stacking visualization */
const ContextLayers: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const layers = [
    {label: 'Visit transcript', color: COLORS.sky[400]},
    {label: 'Patient record', color: COLORS.emerald[400]},
    {label: 'Health history', color: COLORS.violet[400]},
    {label: 'Medications + FDA', color: COLORS.amber[400]},
    {label: 'Clinical guidelines', color: COLORS.rose[400]},
  ];

  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        gap: 6,
        padding: '16px 20px',
      }}
    >
      {layers.map((layer, i) => {
        const layerAppear = spring({
          frame,
          fps,
          delay: delay + i * 6,
          config: {damping: 16, stiffness: 140},
        });

        return (
          <div
            key={layer.label}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 10,
              opacity: layerAppear,
              transform: `translateX(${interpolate(layerAppear, [0, 1], [-20, 0])}px)`,
            }}
          >
            <div
              style={{
                width: 4,
                height: 20,
                borderRadius: 2,
                background: layer.color,
                boxShadow: `0 0 8px ${layer.color}44`,
              }}
            />
            <span
              style={{
                fontSize: 12,
                color: COLORS.slate[300],
                fontFamily: FONT.mono,
              }}
            >
              {layer.label}
            </span>
          </div>
        );
      })}
    </div>
  );
};

/** Verification badges */
const VerificationBadges: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const checks = [
    'Drug interaction safe',
    'Dosage verified',
    'Guideline compliant',
  ];

  return (
    <div style={{display: 'flex', flexDirection: 'column', gap: 6, padding: '12px 16px'}}>
      {checks.map((check, i) => {
        const checkAppear = spring({
          frame,
          fps,
          delay: delay + i * 8,
          config: {damping: 14, stiffness: 160},
        });

        return (
          <div
            key={check}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 8,
              opacity: checkAppear,
              transform: `scale(${interpolate(checkAppear, [0, 1], [0.8, 1])})`,
              transformOrigin: 'left center',
            }}
          >
            <svg width={14} height={14} viewBox="0 0 14 14" fill="none">
              <circle cx={7} cy={7} r={6} fill={`${COLORS.emerald[500]}33`} />
              <path
                d="M4 7l2 2 4-4"
                stroke={COLORS.emerald[400]}
                strokeWidth={1.5}
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
            <span
              style={{
                fontSize: 11,
                color: COLORS.emerald[300],
                fontFamily: FONT.mono,
              }}
            >
              {check}
            </span>
          </div>
        );
      })}
    </div>
  );
};

/** Thinking stream preview with scrolling text */
const ThinkingStream: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({
    frame,
    fps,
    delay,
    config: {damping: 18, stiffness: 120},
  });

  const thinkingLines = [
    'Checking propranolol contraindications...',
    'Cross-referencing patient allergy history...',
    'Verifying against ESC 2024 guidelines...',
    'Evaluating NSAID + beta-blocker interaction...',
    'Assessing renal function implications...',
    'Formulating evidence-based recommendation...',
    'Running safety verification layer...',
  ];

  const scrollOffset = interpolate(frame - delay, [0, fps * 1.5], [0, -80], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  return (
    <div
      style={{
        opacity: appear,
        transform: `translateY(${interpolate(appear, [0, 1], [20, 0])}px)`,
        width: 700,
        height: 80,
        borderRadius: 10,
        background: COLORS.slate[800],
        border: `1px solid ${COLORS.slate[700]}`,
        overflow: 'hidden',
        padding: '12px 16px',
        position: 'relative',
      }}
    >
      {/* Gradient fade top */}
      <div
        style={{
          position: 'absolute',
          top: 0,
          left: 0,
          right: 0,
          height: 20,
          background: `linear-gradient(180deg, ${COLORS.slate[800]} 0%, transparent 100%)`,
          zIndex: 2,
        }}
      />
      {/* Gradient fade bottom */}
      <div
        style={{
          position: 'absolute',
          bottom: 0,
          left: 0,
          right: 0,
          height: 20,
          background: `linear-gradient(0deg, ${COLORS.slate[800]} 0%, transparent 100%)`,
          zIndex: 2,
        }}
      />
      {/* Scrolling text */}
      <div style={{transform: `translateY(${scrollOffset}px)`}}>
        {thinkingLines.map((line, i) => (
          <div
            key={i}
            style={{
              fontSize: 11,
              color: COLORS.slate[500],
              fontFamily: FONT.mono,
              lineHeight: 2,
              whiteSpace: 'nowrap',
            }}
          >
            <span style={{color: COLORS.emerald[500]}}>{'> '}</span>
            {line}
          </div>
        ))}
      </div>
    </div>
  );
};

/* ---------- main composition ---------- */

export const DeepReasoning: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const titleStart = 0;
  const pipelineStart = Math.round(fps * 1.5);
  const phase1ActiveStart = Math.round(fps * 2);
  const phase1CompleteStart = Math.round(fps * 4);
  const phase2ActiveStart = Math.round(fps * 4);
  const phase2CompleteStart = Math.round(fps * 6.5);
  const phase3ActiveStart = Math.round(fps * 6.5);
  const phase3CompleteStart = Math.round(fps * 9);
  const streamStart = Math.round(fps * 9);
  const resultStart = Math.round(fps * 10.5);

  // Title fade out
  const titleFade = interpolate(
    frame,
    [pipelineStart - 5, pipelineStart + 15],
    [1, 0],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  );

  // Pipeline slides up and becomes prominent
  const pipelineAppear = spring({
    frame,
    fps,
    delay: pipelineStart,
    config: {damping: 22, stiffness: 100},
  });
  const pipelineY = interpolate(pipelineAppear, [0, 1], [40, 0], {
    extrapolateRight: 'clamp',
  });

  return (
    <AbsoluteFill>
      <Background variant="radial" />

      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'flex-start',
          paddingTop: 50,
        }}
      >
        {/* Logo */}
        <div style={{marginBottom: 20}}>
          <Logo size={36} showBadge delay={0} />
        </div>

        {/* Title */}
        <div style={{opacity: titleFade, textAlign: 'center', marginBottom: 10}}>
          <WordReveal
            text="Clinical Reasoning Pipeline"
            fontSize={48}
            delay={5}
          />
          <div style={{marginTop: 10}}>
            <WordReveal
              text="Opus 4.6 Extended Thinking"
              fontSize={22}
              color={COLORS.slate[400]}
              fontWeight={400}
              delay={15}
            />
          </div>
        </div>

        {/* Pipeline visualization */}
        <div
          style={{
            opacity: pipelineAppear,
            transform: `translateY(${pipelineY}px)`,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: 30,
          }}
        >
          {/* Three phases horizontal */}
          <div
            style={{
              display: 'flex',
              alignItems: 'flex-start',
              gap: 0,
            }}
          >
            <PhaseBox
              label="PLAN"
              subtitle="Identifying knowledge sources"
              phase="plan"
              delay={pipelineStart}
              activeDelay={phase1ActiveStart}
              completeDelay={phase1CompleteStart}
            />
            <ConnectingArrow
              delay={pipelineStart + 4}
              activeDelay={phase1CompleteStart}
            />
            <PhaseBox
              label="EXECUTE"
              subtitle="Assembling clinical context"
              phase="execute"
              delay={pipelineStart + 6}
              activeDelay={phase2ActiveStart}
              completeDelay={phase2CompleteStart}
            />
            <ConnectingArrow
              delay={pipelineStart + 10}
              activeDelay={phase2CompleteStart}
            />
            <PhaseBox
              label="VERIFY"
              subtitle="Safety verification"
              phase="verify"
              delay={pipelineStart + 12}
              activeDelay={phase3ActiveStart}
              completeDelay={phase3CompleteStart}
            />
          </div>

          {/* Detail panels that appear below during active phases */}
          <div
            style={{
              width: 900,
              minHeight: 120,
              display: 'flex',
              justifyContent: 'center',
              position: 'relative',
            }}
          >
            {/* Context layers (phase 2) */}
            {frame >= phase2ActiveStart && frame < phase3CompleteStart && (
              <div
                style={{
                  position: 'absolute',
                  left: '50%',
                  transform: 'translateX(-70%)',
                }}
              >
                <ContextLayers delay={phase2ActiveStart} />
              </div>
            )}

            {/* Verification badges (phase 3) */}
            {frame >= phase3ActiveStart && (
              <div
                style={{
                  position: 'absolute',
                  left: '50%',
                  transform: 'translateX(10%)',
                }}
              >
                <VerificationBadges delay={phase3ActiveStart} />
              </div>
            )}
          </div>

          {/* Thinking stream preview */}
          <Sequence from={streamStart} premountFor={15}>
            <div style={{display: 'flex', flexDirection: 'column', alignItems: 'center'}}>
              <ThinkingStream delay={streamStart} />
            </div>
          </Sequence>

          {/* Result badges */}
          <Sequence from={resultStart} premountFor={10}>
            <div style={{display: 'flex', gap: 16, alignItems: 'center'}}>
              <Badge
                text="7-layer context assembly"
                variant="emerald"
                delay={resultStart}
              />
              <Badge
                text="Extended thinking: 2,847 tokens"
                variant="amber"
                delay={resultStart + 6}
              />
            </div>
          </Sequence>
        </div>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
