import React from 'react';
import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  spring,
  interpolate,
  Easing,
} from 'remotion';
import {COLORS, FONT} from '../theme';
import {Background} from '../components/Background';

const PIPELINE_STEPS = [
  {name: 'Transcribing audio', icon: 'waveform'},
  {name: 'Extracting clinical info', icon: 'extract'},
  {name: 'Building visit summary', icon: 'document'},
  {name: 'Cross-referencing guidelines', icon: 'reference'},
  {name: 'Checking medications', icon: 'pill'},
  {name: 'Preparing your summary', icon: 'check'},
] as const;

/** SVG icons for each pipeline step */
const StepIcon: React.FC<{type: string; color: string; size?: number}> = ({
  type,
  color,
  size = 20,
}) => {
  const half = size / 2;

  switch (type) {
    case 'waveform':
      return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
          <path d="M4 12h2M8 8v8M12 5v14M16 8v8M20 12h2" stroke={color} strokeWidth="2" strokeLinecap="round" />
        </svg>
      );
    case 'extract':
      return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
          <rect x="4" y="4" width="16" height="16" rx="2" stroke={color} strokeWidth="2" />
          <path d="M8 9h8M8 13h5" stroke={color} strokeWidth="2" strokeLinecap="round" />
        </svg>
      );
    case 'document':
      return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
          <path d="M6 2h8l6 6v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" stroke={color} strokeWidth="2" />
          <path d="M14 2v6h6" stroke={color} strokeWidth="2" />
        </svg>
      );
    case 'reference':
      return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="9" stroke={color} strokeWidth="2" />
          <path d="M12 8v4l3 3" stroke={color} strokeWidth="2" strokeLinecap="round" />
        </svg>
      );
    case 'pill':
      return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
          <rect x="5" y="9" width="14" height="6" rx="3" stroke={color} strokeWidth="2" transform="rotate(-45 12 12)" />
          <line x1="12" y1="8" x2="12" y2="16" stroke={color} strokeWidth="2" transform="rotate(-45 12 12)" />
        </svg>
      );
    case 'check':
      return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
          <circle cx={half} cy={half} r={half - 2} stroke={color} strokeWidth="2" fill="none" />
          <path d="M7 12l3 3 7-7" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      );
    default:
      return (
        <svg width={size} height={size} viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="8" fill={color} />
        </svg>
      );
  }
};

/** A single pipeline step row with progress bar */
const PipelineStep: React.FC<{
  name: string;
  icon: string;
  index: number;
  stepStartFrame: number;
  stepDurationFrames: number;
}> = ({name, icon, index, stepStartFrame, stepDurationFrames}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // Row appearance
  const rowAppear = spring({
    frame,
    fps,
    delay: stepStartFrame,
    config: {damping: 200},
  });

  // Progress bar fill
  const fillStart = stepStartFrame + 5;
  const fillEnd = stepStartFrame + stepDurationFrames;
  const progress = interpolate(frame, [fillStart, fillEnd], [0, 100], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
    easing: Easing.inOut(Easing.cubic),
  });

  const isComplete = progress >= 100;

  // Check mark spring
  const checkAppear = spring({
    frame,
    fps,
    delay: fillEnd,
    config: {damping: 12, stiffness: 200},
  });

  const iconColor = isComplete ? COLORS.emerald[400] : COLORS.slate[400];
  const nameColor = isComplete ? COLORS.emerald[300] : COLORS.slate[300];

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: 24,
        opacity: rowAppear,
        transform: `translateY(${interpolate(rowAppear, [0, 1], [20, 0])}px)`,
      }}
    >
      {/* Step number / icon */}
      <div
        style={{
          width: 48,
          height: 48,
          borderRadius: 14,
          background: isComplete
            ? `linear-gradient(135deg, ${COLORS.emerald[600]}44, ${COLORS.emerald[500]}22)`
            : `${COLORS.slate[800]}`,
          border: `1px solid ${isComplete ? COLORS.emerald[500] + '55' : COLORS.slate[700]}`,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          flexShrink: 0,
        }}
      >
        <StepIcon type={icon} color={iconColor} size={22} />
      </div>

      {/* Step name and progress */}
      <div style={{flex: 1, display: 'flex', flexDirection: 'column', gap: 8}}>
        <span
          style={{
            fontSize: 20,
            fontWeight: 600,
            color: nameColor,
            fontFamily: FONT.body,
          }}
        >
          {name}
        </span>

        {/* Progress bar */}
        <div
          style={{
            width: '100%',
            height: 5,
            borderRadius: 3,
            backgroundColor: COLORS.slate[700],
            overflow: 'hidden',
          }}
        >
          <div
            style={{
              width: `${progress}%`,
              height: '100%',
              borderRadius: 3,
              background: `linear-gradient(90deg, ${COLORS.emerald[600]}, ${COLORS.emerald[400]})`,
            }}
          />
        </div>
      </div>

      {/* Check mark */}
      <div
        style={{
          width: 32,
          height: 32,
          borderRadius: '50%',
          backgroundColor: isComplete ? COLORS.emerald[500] : 'transparent',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          opacity: checkAppear,
          transform: `scale(${interpolate(checkAppear, [0, 1], [0.5, 1])})`,
          flexShrink: 0,
        }}
      >
        {isComplete && (
          <svg width={16} height={16} viewBox="0 0 24 24" fill="none">
            <path
              d="M5 13l4 4L19 7"
              stroke={COLORS.white}
              strokeWidth="3"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        )}
      </div>
    </div>
  );
};

/** Pulsing "Ready" badge at the end */
const ReadyBadge: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({
    frame,
    fps,
    delay,
    config: {damping: 12, stiffness: 150},
  });

  // Gentle pulse after appearance
  const localFrame = Math.max(0, frame - delay);
  const pulse = 1 + Math.sin(localFrame * 0.15) * 0.03;
  const combinedScale = interpolate(appear, [0, 1], [0.5, 1]) * pulse;

  const glowIntensity = interpolate(
    Math.sin(localFrame * 0.12),
    [-1, 1],
    [0.3, 0.6],
  );

  return (
    <div
      style={{
        opacity: appear,
        transform: `scale(${combinedScale})`,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 16,
      }}
    >
      {/* Glow ring */}
      <div
        style={{
          width: 120,
          height: 120,
          borderRadius: '50%',
          background: `linear-gradient(135deg, ${COLORS.emerald[500]}, ${COLORS.emerald[600]})`,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          boxShadow: `0 0 60px ${COLORS.emerald[500]}${Math.round(glowIntensity * 255).toString(16).padStart(2, '0')}`,
        }}
      >
        <svg width={56} height={56} viewBox="0 0 24 24" fill="none">
          <path
            d="M5 13l4 4L19 7"
            stroke={COLORS.white}
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </div>
      <span
        style={{
          fontSize: 32,
          fontWeight: 800,
          color: COLORS.emerald[400],
          fontFamily: FONT.heading,
          letterSpacing: '0.08em',
          textTransform: 'uppercase',
        }}
      >
        Ready
      </span>
      <span
        style={{
          fontSize: 18,
          color: COLORS.slate[400],
          fontWeight: 400,
        }}
      >
        Your visit summary has been prepared
      </span>
    </div>
  );
};

/**
 * VIDEO 3: AI Processing Pipeline (10 seconds / 300 frames @ 30fps)
 *
 * Shows the 6-step AI processing that happens after recording:
 * sequential step animations with progress bars and a final "Ready" badge.
 */
export const AiProcessing: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // --- Title (0-1s) ---
  const titleAppear = spring({
    frame,
    fps,
    delay: 0,
    config: {damping: 200},
  });

  // --- Pipeline steps (1-8s): 6 steps across 7 seconds ---
  const pipelineStart = fps * 1;
  const pipelineDuration = fps * 7; // frames for all 6 steps
  const stepDuration = Math.floor(pipelineDuration / PIPELINE_STEPS.length);

  // --- Are all steps complete? (for transitioning to Ready) ---
  const allStepsEnd = pipelineStart + PIPELINE_STEPS.length * stepDuration;
  const allComplete = frame >= allStepsEnd;

  // --- Steps fade out when Ready badge appears (8-8.5s) ---
  const stepsOpacity = interpolate(
    frame,
    [fps * 7.8, fps * 8.3],
    [1, 0],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  );

  // --- Ready badge (8-10s) ---
  const readyDelay = fps * 8;

  return (
    <AbsoluteFill>
      <Background variant="dark" />

      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '0 200px',
        }}
      >
        {/* Title */}
        <div
          style={{
            opacity: allComplete ? stepsOpacity : titleAppear,
            transform: `translateY(${interpolate(titleAppear, [0, 1], [-30, 0])}px)`,
            marginBottom: 48,
            textAlign: 'center',
          }}
        >
          <span
            style={{
              fontSize: 48,
              fontWeight: 800,
              color: COLORS.white,
              fontFamily: FONT.heading,
            }}
          >
            AI Processing{' '}
            <span style={{color: COLORS.emerald[400]}}>Pipeline</span>
          </span>
        </div>

        {/* Pipeline steps (fade out when Ready appears) */}
        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            gap: 20,
            width: '100%',
            maxWidth: 700,
            opacity: stepsOpacity,
          }}
        >
          {PIPELINE_STEPS.map((step, i) => (
            <PipelineStep
              key={step.name}
              name={step.name}
              icon={step.icon}
              index={i}
              stepStartFrame={pipelineStart + i * stepDuration}
              stepDurationFrames={stepDuration - 5}
            />
          ))}
        </div>

        {/* Ready badge (replaces steps) */}
        {frame >= readyDelay - 10 && (
          <div
            style={{
              position: 'absolute',
              display: 'flex',
              justifyContent: 'center',
              alignItems: 'center',
            }}
          >
            <ReadyBadge delay={readyDelay} />
          </div>
        )}
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
