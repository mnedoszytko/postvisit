import React from 'react';
import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  spring,
  interpolate,
  Sequence,
  Easing,
} from 'remotion';
import {COLORS, FONT} from '../theme';
import {Background} from '../components/Background';
import {MockPhone} from '../components/MockPhone';
import {Badge} from '../components/Badge';

const LEFT_TEXT_LINES = [
  'Patient records the visit',
  'Automatic transcription',
  'AI extracts clinical data',
] as const;

const PROCESSING_STEPS = [
  'Transcribing audio...',
  'Extracting entities...',
  'Building SOAP note...',
] as const;

/** Animated audio waveform bars driven by useCurrentFrame */
const AudioWaveform: React.FC<{active: boolean}> = ({active}) => {
  const frame = useCurrentFrame();
  const barCount = 5;
  const barWidth = 8;
  const gap = 6;
  const maxHeight = 48;
  const minHeight = 12;

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap,
        height: maxHeight + 4,
      }}
    >
      {Array.from({length: barCount}).map((_, i) => {
        // Each bar oscillates at a different phase and frequency
        const phase = i * 1.3;
        const speed = 0.12 + i * 0.02;
        const rawHeight = active
          ? minHeight +
            (maxHeight - minHeight) *
              ((Math.sin(frame * speed + phase) + 1) / 2)
          : minHeight;

        return (
          <div
            key={i}
            style={{
              width: barWidth,
              height: rawHeight,
              borderRadius: barWidth / 2,
              backgroundColor: COLORS.emerald[400],
            }}
          />
        );
      })}
    </div>
  );
};

/** Recording timer display */
const RecordingTimer: React.FC<{startFrame: number; endFrame: number}> = ({
  startFrame,
  endFrame,
}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const elapsed = interpolate(frame, [startFrame, endFrame], [0, 154], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  const minutes = Math.floor(elapsed / 60);
  const seconds = Math.floor(elapsed % 60);
  const display = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

  // Blinking red dot
  const dotOpacity = frame % fps < fps / 2 ? 1 : 0.3;

  return (
    <div style={{display: 'flex', alignItems: 'center', gap: 10}}>
      <div
        style={{
          width: 10,
          height: 10,
          borderRadius: '50%',
          backgroundColor: COLORS.rose[500],
          opacity: dotOpacity,
        }}
      />
      <span
        style={{
          fontSize: 28,
          fontFamily: FONT.mono,
          color: COLORS.white,
          fontWeight: 500,
          letterSpacing: '0.05em',
        }}
      >
        {display}
      </span>
    </div>
  );
};

/** Phone content: Recording state */
const RecordingScreen: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // The recording state is visible from frames 0-150 (0-5s in the phone context)
  const isRecording = frame < fps * 5;

  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'space-between',
        height: '100%',
        padding: '40px 24px 36px',
        backgroundColor: COLORS.slate[900],
      }}
    >
      {/* Header */}
      <div
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: 4,
        }}
      >
        <span
          style={{
            fontSize: 14,
            fontWeight: 500,
            color: COLORS.emerald[400],
            textTransform: 'uppercase',
            letterSpacing: '0.1em',
          }}
        >
          PostVisit.ai
        </span>
        <span
          style={{
            fontSize: 22,
            fontWeight: 700,
            color: COLORS.white,
          }}
        >
          Recording Visit
        </span>
      </div>

      {/* Waveform + Timer */}
      <div
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: 24,
        }}
      >
        <AudioWaveform active={isRecording} />
        <RecordingTimer startFrame={0} endFrame={fps * 5} />
      </div>

      {/* Stop button */}
      <div
        style={{
          width: 72,
          height: 72,
          borderRadius: '50%',
          backgroundColor: COLORS.rose[500],
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          boxShadow: `0 0 24px ${COLORS.rose[500]}66`,
        }}
      >
        <div
          style={{
            width: 24,
            height: 24,
            borderRadius: 4,
            backgroundColor: COLORS.white,
          }}
        />
      </div>
    </div>
  );
};

/** Phone content: Processing state */
const ProcessingScreen: React.FC<{entryFrame: number}> = ({entryFrame}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const localFrame = frame - entryFrame;

  // Overall progress bar: fills from 0% to 100% over 3 seconds
  const progress = interpolate(localFrame, [0, fps * 3], [0, 100], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
    easing: Easing.out(Easing.cubic),
  });

  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        height: '100%',
        padding: '40px 24px',
        backgroundColor: COLORS.slate[900],
        gap: 32,
      }}
    >
      {/* Header */}
      <div style={{display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 4}}>
        <span
          style={{
            fontSize: 14,
            fontWeight: 500,
            color: COLORS.emerald[400],
            textTransform: 'uppercase',
            letterSpacing: '0.1em',
          }}
        >
          PostVisit.ai
        </span>
        <span style={{fontSize: 22, fontWeight: 700, color: COLORS.white}}>
          Processing...
        </span>
      </div>

      {/* Spinner ring */}
      <div
        style={{
          width: 64,
          height: 64,
          borderRadius: '50%',
          border: `3px solid ${COLORS.slate[700]}`,
          borderTopColor: COLORS.emerald[400],
          transform: `rotate(${localFrame * 8}deg)`,
        }}
      />

      {/* Progress bar */}
      <div
        style={{
          width: '80%',
          height: 6,
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
            background: `linear-gradient(90deg, ${COLORS.emerald[500]}, ${COLORS.emerald[400]})`,
          }}
        />
      </div>

      {/* Processing steps */}
      <div style={{display: 'flex', flexDirection: 'column', gap: 20, width: '80%'}}>
        {PROCESSING_STEPS.map((step, i) => {
          const stepStart = i * fps * 0.9;
          const stepAppear = spring({
            frame: localFrame,
            fps,
            delay: stepStart,
            config: {damping: 200},
          });
          const stepDone = localFrame > stepStart + fps * 0.8;

          return (
            <div
              key={step}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                opacity: stepAppear,
                transform: `translateX(${interpolate(stepAppear, [0, 1], [20, 0])}px)`,
              }}
            >
              {/* Check or spinner dot */}
              <div
                style={{
                  width: 22,
                  height: 22,
                  borderRadius: '50%',
                  backgroundColor: stepDone
                    ? COLORS.emerald[500]
                    : COLORS.slate[700],
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  flexShrink: 0,
                }}
              >
                {stepDone && (
                  <svg width={12} height={12} viewBox="0 0 24 24" fill="none">
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
              <span
                style={{
                  fontSize: 16,
                  color: stepDone ? COLORS.emerald[300] : COLORS.slate[400],
                  fontWeight: stepDone ? 600 : 400,
                }}
              >
                {step}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
};

/** Phone content: Done state */
const DoneScreen: React.FC<{entryFrame: number}> = ({entryFrame}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const localFrame = frame - entryFrame;
  const checkScale = spring({
    frame: localFrame,
    fps,
    delay: 0,
    config: {damping: 12, stiffness: 150},
  });

  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        height: '100%',
        backgroundColor: COLORS.slate[900],
        gap: 24,
      }}
    >
      {/* Big check circle */}
      <div
        style={{
          width: 96,
          height: 96,
          borderRadius: '50%',
          background: `linear-gradient(135deg, ${COLORS.emerald[500]}, ${COLORS.emerald[600]})`,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          transform: `scale(${checkScale})`,
          boxShadow: `0 0 40px ${COLORS.emerald[500]}44`,
        }}
      >
        <svg width={48} height={48} viewBox="0 0 24 24" fill="none">
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
          fontSize: 24,
          fontWeight: 700,
          color: COLORS.white,
          opacity: interpolate(localFrame, [fps * 0.3, fps * 0.8], [0, 1], {
            extrapolateLeft: 'clamp',
            extrapolateRight: 'clamp',
          }),
        }}
      >
        Visit Processed
      </span>
      <span
        style={{
          fontSize: 16,
          color: COLORS.slate[400],
          opacity: interpolate(localFrame, [fps * 0.5, fps * 1], [0, 1], {
            extrapolateLeft: 'clamp',
            extrapolateRight: 'clamp',
          }),
        }}
      >
        Your summary is ready
      </span>
    </div>
  );
};

/**
 * VIDEO 2: Reverse AI Scribe (12 seconds / 360 frames @ 30fps)
 *
 * Shows the innovative "companion scribe" concept: recording a doctor visit,
 * AI processing, and completion.
 */
export const ReverseScribe: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // --- Title slide-in from left (0-1s) ---
  const titleAppear = spring({
    frame,
    fps,
    delay: 0,
    config: {damping: 200},
  });

  // --- Phase management ---
  const isRecording = frame < fps * 5;
  const isProcessing = frame >= fps * 5 && frame < fps * 8;
  const isDone = frame >= fps * 8;

  // --- Left side text (3-5s) ---
  // Staggered line reveals
  const textLines = LEFT_TEXT_LINES.map((line, i) => {
    const lineDelay = fps * 3 + i * 12;
    const lineSpring = spring({
      frame,
      fps,
      delay: lineDelay,
      config: {damping: 200},
    });
    return {text: line, opacity: lineSpring, translateX: interpolate(lineSpring, [0, 1], [-40, 0])};
  });

  // --- Bottom badge (10-12s) ---
  const badgeDelay = fps * 10;

  return (
    <AbsoluteFill>
      <Background variant="dark" />

      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'row',
          alignItems: 'center',
          padding: '0 100px',
        }}
      >
        {/* LEFT SIDE (40%) */}
        <div
          style={{
            flex: '0 0 40%',
            display: 'flex',
            flexDirection: 'column',
            gap: 40,
            paddingRight: 60,
          }}
        >
          {/* Title */}
          <div
            style={{
              opacity: titleAppear,
              transform: `translateX(${interpolate(titleAppear, [0, 1], [-60, 0])}px)`,
            }}
          >
            <span
              style={{
                fontSize: 52,
                fontWeight: 800,
                color: COLORS.white,
                fontFamily: FONT.heading,
                lineHeight: 1.15,
              }}
            >
              Reverse AI{' '}
              <span style={{color: COLORS.emerald[400]}}>Scribe</span>
            </span>
          </div>

          {/* Description lines */}
          <div style={{display: 'flex', flexDirection: 'column', gap: 22}}>
            {textLines.map((line, i) => (
              <div
                key={i}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 14,
                  opacity: line.opacity,
                  transform: `translateX(${line.translateX}px)`,
                }}
              >
                <div
                  style={{
                    width: 8,
                    height: 8,
                    borderRadius: '50%',
                    backgroundColor: COLORS.emerald[500],
                    flexShrink: 0,
                  }}
                />
                <span
                  style={{
                    fontSize: 22,
                    color: COLORS.slate[300],
                    fontWeight: 400,
                    fontFamily: FONT.body,
                  }}
                >
                  {line.text}
                </span>
              </div>
            ))}
          </div>

          {/* Bottom badge */}
          <div style={{marginTop: 20}}>
            <Badge
              text="No more forgotten instructions"
              variant="emerald"
              delay={badgeDelay}
            />
          </div>
        </div>

        {/* RIGHT SIDE (60%) - Phone */}
        <div
          style={{
            flex: '0 0 60%',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
          }}
        >
          <MockPhone delay={fps * 1} scale={0.9}>
            {isRecording && <RecordingScreen />}
            {isProcessing && <ProcessingScreen entryFrame={fps * 5} />}
            {isDone && <DoneScreen entryFrame={fps * 8} />}
          </MockPhone>
        </div>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
