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
import {WordReveal, LineReveal} from '../components/AnimatedText';
import {MockBrowser} from '../components/MockPhone';
import {Badge} from '../components/Badge';

// --- Pipeline Steps ---

const PIPELINE_STEPS = [
  {label: 'Extracting text', icon: 'document'},
  {label: 'Analyzing content', icon: 'magnifier'},
  {label: 'Categorizing', icon: 'tag'},
  {label: 'Relating to your health', icon: 'heart'},
  {label: 'Verifying sources', icon: 'check'},
] as const;

// --- Step Icon Renderer ---

const StepIcon: React.FC<{
  icon: string;
  color: string;
  size?: number;
}> = ({icon, color, size = 20}) => {
  const svgProps = {width: size, height: size, viewBox: '0 0 24 24', fill: 'none'};

  switch (icon) {
    case 'document':
      return (
        <svg {...svgProps}>
          <rect x={5} y={2} width={14} height={20} rx={2} stroke={color} strokeWidth={1.8} />
          <line x1={9} y1={8} x2={15} y2={8} stroke={color} strokeWidth={1.5} strokeLinecap="round" />
          <line x1={9} y1={12} x2={15} y2={12} stroke={color} strokeWidth={1.5} strokeLinecap="round" />
          <line x1={9} y1={16} x2={13} y2={16} stroke={color} strokeWidth={1.5} strokeLinecap="round" />
        </svg>
      );
    case 'magnifier':
      return (
        <svg {...svgProps}>
          <circle cx={10} cy={10} r={6} stroke={color} strokeWidth={1.8} />
          <line x1={14.5} y1={14.5} x2={20} y2={20} stroke={color} strokeWidth={2} strokeLinecap="round" />
        </svg>
      );
    case 'tag':
      return (
        <svg {...svgProps}>
          <path
            d="M3 8V5a2 2 0 012-2h3l9 9-5 5-9-9z"
            stroke={color}
            strokeWidth={1.8}
            strokeLinejoin="round"
          />
          <circle cx={8} cy={8} r={1.5} fill={color} />
        </svg>
      );
    case 'heart':
      return (
        <svg {...svgProps}>
          <path
            d="M12 20l-7-7a4.5 4.5 0 010-6.4 4.5 4.5 0 016.4 0L12 7.2l.6-.6a4.5 4.5 0 016.4 6.4L12 20z"
            stroke={color}
            strokeWidth={1.8}
            strokeLinejoin="round"
          />
        </svg>
      );
    case 'check':
      return (
        <svg {...svgProps}>
          <circle cx={12} cy={12} r={9} stroke={color} strokeWidth={1.8} />
          <path d="M8 12l3 3 5-5" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      );
    default:
      return null;
  }
};

// --- Upload Zone ---

const UploadZone: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const zoneAppear = spring({
    frame,
    fps,
    delay,
    config: {damping: 200},
  });

  // PDF icon drop animation - bouncy spring
  const pdfDrop = spring({
    frame,
    fps,
    delay: delay + 12,
    config: {damping: 12, stiffness: 120, mass: 0.8},
  });

  const pdfY = interpolate(pdfDrop, [0, 1], [-80, 0], {
    extrapolateRight: 'clamp',
  });

  const filenameAppear = spring({
    frame,
    fps,
    delay: delay + 25,
    config: {damping: 200},
  });

  return (
    <div
      style={{
        padding: 24,
        opacity: zoneAppear,
      }}
    >
      <div
        style={{
          border: `2px dashed ${COLORS.slate[600]}`,
          borderRadius: 12,
          padding: '32px 24px',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: 12,
          background: `${COLORS.slate[800]}88`,
        }}
      >
        {/* PDF icon */}
        <div
          style={{
            transform: `translateY(${pdfY}px)`,
            opacity: pdfDrop,
          }}
        >
          <div
            style={{
              width: 56,
              height: 68,
              background: COLORS.rose[500],
              borderRadius: 8,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              position: 'relative',
              boxShadow: `0 4px 20px ${COLORS.rose[500]}44`,
            }}
          >
            {/* Corner fold */}
            <div
              style={{
                position: 'absolute',
                top: 0,
                right: 0,
                width: 16,
                height: 16,
                background: COLORS.rose[400],
                borderBottomLeftRadius: 6,
              }}
            />
            <span
              style={{
                fontSize: 14,
                fontWeight: 800,
                color: COLORS.white,
                fontFamily: FONT.body,
                letterSpacing: '0.05em',
              }}
            >
              PDF
            </span>
          </div>
        </div>

        {/* Filename */}
        <div
          style={{
            opacity: filenameAppear,
            transform: `translateY(${interpolate(filenameAppear, [0, 1], [10, 0])}px)`,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: 4,
          }}
        >
          <span
            style={{
              fontSize: 14,
              fontWeight: 600,
              color: COLORS.white,
              fontFamily: FONT.mono,
            }}
          >
            WHO_CVD_Risk_Assessment.pdf
          </span>
          <span
            style={{
              fontSize: 12,
              color: COLORS.slate[400],
              fontFamily: FONT.body,
            }}
          >
            2.4 MB - Uploaded
          </span>
        </div>
      </div>
    </div>
  );
};

// --- Analysis Pipeline ---

const AnalysisPipeline: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const stepInterval = 12; // frames between each step activation
  const stepWidth = 180;
  const totalWidth = stepWidth * PIPELINE_STEPS.length;
  const lineGap = 20;

  return (
    <div
      style={{
        padding: '8px 24px 16px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
      }}
    >
      <div
        style={{
          display: 'flex',
          alignItems: 'flex-start',
          gap: 0,
          position: 'relative',
          width: totalWidth,
        }}
      >
        {PIPELINE_STEPS.map((step, i) => {
          const stepDelay = delay + i * stepInterval;

          const appear = spring({
            frame,
            fps,
            delay: stepDelay,
            config: {damping: 200},
          });

          const activate = spring({
            frame,
            fps,
            delay: stepDelay + 6,
            config: {damping: 200},
          });

          const isActive = activate > 0.5;

          // Color transition: slate -> emerald
          const bgColor = isActive
            ? interpolate(activate, [0, 1], [0, 1], {extrapolateRight: 'clamp'})
            : 0;

          const bg = `rgba(${interpolate(bgColor, [0, 1], [30, 16])}, ${interpolate(bgColor, [0, 1], [41, 185])}, ${interpolate(bgColor, [0, 1], [59, 129])}, ${interpolate(bgColor, [0, 1], [0.3, 0.15])})`;
          const borderColor = `rgba(${interpolate(bgColor, [0, 1], [71, 52])}, ${interpolate(bgColor, [0, 1], [85, 211])}, ${interpolate(bgColor, [0, 1], [105, 153])}, ${interpolate(bgColor, [0, 1], [0.4, 0.5])})`;
          const iconColor = interpolate(
            bgColor,
            [0, 1],
            [0, 1],
          );
          const resolvedIconColor = iconColor > 0.5 ? COLORS.emerald[400] : COLORS.slate[400];

          // Connection line to next step
          const hasLine = i < PIPELINE_STEPS.length - 1;
          const lineProgress = spring({
            frame,
            fps,
            delay: stepDelay + stepInterval - 4,
            config: {damping: 200},
          });

          return (
            <div
              key={step.label}
              style={{
                display: 'flex',
                alignItems: 'center',
                position: 'relative',
              }}
            >
              {/* Step card */}
              <div
                style={{
                  width: stepWidth - lineGap,
                  opacity: appear,
                  transform: `translateY(${interpolate(appear, [0, 1], [20, 0])}px)`,
                  background: bg,
                  border: `1px solid ${borderColor}`,
                  borderRadius: 10,
                  padding: '14px 12px',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: 8,
                }}
              >
                {/* Icon or checkmark */}
                <div
                  style={{
                    width: 36,
                    height: 36,
                    borderRadius: '50%',
                    background: isActive
                      ? `${COLORS.emerald[500]}22`
                      : `${COLORS.slate[600]}44`,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  {isActive ? (
                    <svg width={18} height={18} viewBox="0 0 24 24" fill="none">
                      <path
                        d="M5 12l5 5L19 7"
                        stroke={COLORS.emerald[400]}
                        strokeWidth={2.5}
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                  ) : (
                    <StepIcon icon={step.icon} color={resolvedIconColor} size={18} />
                  )}
                </div>
                <span
                  style={{
                    fontSize: 12,
                    fontWeight: 600,
                    color: isActive ? COLORS.emerald[300] : COLORS.slate[400],
                    fontFamily: FONT.body,
                    textAlign: 'center',
                    lineHeight: 1.3,
                  }}
                >
                  {step.label}
                </span>
              </div>

              {/* Connection line */}
              {hasLine && (
                <div
                  style={{
                    width: lineGap,
                    height: 2,
                    position: 'relative',
                    overflow: 'hidden',
                  }}
                >
                  <div
                    style={{
                      position: 'absolute',
                      top: 0,
                      left: 0,
                      height: '100%',
                      width: `${interpolate(lineProgress, [0, 1], [0, 100], {extrapolateRight: 'clamp'})}%`,
                      background: COLORS.emerald[500],
                      borderRadius: 1,
                    }}
                  />
                  <div
                    style={{
                      position: 'absolute',
                      top: 0,
                      left: 0,
                      height: '100%',
                      width: '100%',
                      background: COLORS.slate[700],
                      borderRadius: 1,
                      zIndex: -1,
                    }}
                  />
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};

// --- Results Panel ---

const FINDINGS = [
  'Patients with LDL < 130 show 40% reduced CVD risk',
  'Regular monitoring of K+ levels recommended with ACE inhibitors',
  'PVC frequency correlates with beta-blocker dosage compliance',
];

const ResultsPanel: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const panelSlide = spring({
    frame,
    fps,
    delay,
    config: {damping: 200},
  });

  // Animated counter for relevance score
  const counterProgress = interpolate(
    frame,
    [delay + 15, delay + 45],
    [0, 87],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  );

  return (
    <div
      style={{
        padding: '8px 24px',
        display: 'flex',
        gap: 20,
        opacity: panelSlide,
        transform: `translateX(${interpolate(panelSlide, [0, 1], [60, 0])}px)`,
      }}
    >
      {/* Left: Findings */}
      <div
        style={{
          flex: 1,
          background: COLORS.slate[800],
          borderRadius: 12,
          padding: 20,
          border: `1px solid ${COLORS.slate[700]}`,
          display: 'flex',
          flexDirection: 'column',
          gap: 14,
        }}
      >
        {/* Evidence badge */}
        <div
          style={{
            display: 'inline-flex',
            alignSelf: 'flex-start',
            padding: '5px 12px',
            borderRadius: 8,
            background: `${COLORS.emerald[500]}22`,
            border: `1px solid ${COLORS.emerald[500]}44`,
          }}
        >
          <span
            style={{
              fontSize: 12,
              fontWeight: 700,
              color: COLORS.emerald[300],
              fontFamily: FONT.body,
            }}
          >
            Level A - Strong Evidence
          </span>
        </div>

        <span
          style={{
            fontSize: 14,
            fontWeight: 600,
            color: COLORS.slate[300],
            fontFamily: FONT.body,
          }}
        >
          Key Findings
        </span>

        {/* Findings list */}
        {FINDINGS.map((finding, i) => {
          const findingAppear = spring({
            frame,
            fps,
            delay: delay + 8 + i * 8,
            config: {damping: 200},
          });

          return (
            <div
              key={i}
              style={{
                display: 'flex',
                gap: 10,
                opacity: findingAppear,
                transform: `translateY(${interpolate(findingAppear, [0, 1], [12, 0])}px)`,
              }}
            >
              <div
                style={{
                  width: 6,
                  height: 6,
                  borderRadius: '50%',
                  background: COLORS.emerald[400],
                  marginTop: 7,
                  flexShrink: 0,
                }}
              />
              <span
                style={{
                  fontSize: 13,
                  color: COLORS.slate[300],
                  fontFamily: FONT.body,
                  lineHeight: 1.5,
                }}
              >
                {finding}
              </span>
            </div>
          );
        })}
      </div>

      {/* Right: Relevance panel */}
      <div
        style={{
          width: 240,
          background: COLORS.slate[800],
          borderRadius: 12,
          padding: 20,
          border: `1px solid ${COLORS.slate[700]}`,
          display: 'flex',
          flexDirection: 'column',
          gap: 16,
          alignItems: 'center',
        }}
      >
        <span
          style={{
            fontSize: 14,
            fontWeight: 600,
            color: COLORS.slate[300],
            fontFamily: FONT.body,
            alignSelf: 'flex-start',
          }}
        >
          Relevance to You
        </span>

        {/* Score ring */}
        <div
          style={{
            width: 100,
            height: 100,
            position: 'relative',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <svg width={100} height={100} viewBox="0 0 100 100">
            {/* Background ring */}
            <circle
              cx={50}
              cy={50}
              r={42}
              fill="none"
              stroke={COLORS.slate[700]}
              strokeWidth={6}
            />
            {/* Progress ring */}
            <circle
              cx={50}
              cy={50}
              r={42}
              fill="none"
              stroke={COLORS.emerald[400]}
              strokeWidth={6}
              strokeLinecap="round"
              strokeDasharray={`${(counterProgress / 100) * 2 * Math.PI * 42} ${2 * Math.PI * 42}`}
              transform="rotate(-90 50 50)"
              style={{
                filter: `drop-shadow(0 0 6px ${COLORS.emerald[500]}66)`,
              }}
            />
          </svg>
          <div
            style={{
              position: 'absolute',
              display: 'flex',
              alignItems: 'baseline',
            }}
          >
            <span
              style={{
                fontSize: 28,
                fontWeight: 700,
                color: COLORS.emerald[400],
                fontFamily: FONT.mono,
              }}
            >
              {Math.round(counterProgress)}
            </span>
            <span
              style={{
                fontSize: 14,
                fontWeight: 600,
                color: COLORS.emerald[400],
                fontFamily: FONT.mono,
              }}
            >
              %
            </span>
          </div>
        </div>

        {/* Matching conditions */}
        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            gap: 6,
            alignSelf: 'stretch',
          }}
        >
          <span
            style={{
              fontSize: 11,
              color: COLORS.slate[500],
              fontFamily: FONT.body,
              textTransform: 'uppercase',
              letterSpacing: '0.05em',
            }}
          >
            Matching Conditions
          </span>
          {['PVC Arrhythmia', 'Hypertension'].map((condition, i) => {
            const condAppear = spring({
              frame,
              fps,
              delay: delay + 20 + i * 6,
              config: {damping: 200},
            });

            return (
              <div
                key={condition}
                style={{
                  opacity: condAppear,
                  padding: '4px 8px',
                  borderRadius: 6,
                  background: `${COLORS.emerald[500]}11`,
                  border: `1px solid ${COLORS.emerald[500]}22`,
                }}
              >
                <span
                  style={{
                    fontSize: 12,
                    color: COLORS.emerald[300],
                    fontWeight: 500,
                    fontFamily: FONT.body,
                  }}
                >
                  {condition}
                </span>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

/**
 * VIDEO 8: Personal Medical Library with AI Analysis (12 seconds / 360 frames @ 30fps)
 *
 * Shows PDF upload and AI analysis pipeline. Sequence:
 * 1. (0-1.5s) Title: "Your Personal Medical Library"
 * 2. (1.5-3.5s) MockBrowser with upload zone, PDF drops in
 * 3. (3.5-7s) Five-step analysis pipeline animates sequentially
 * 4. (7-9.5s) Results panel slides in with findings and relevance score
 * 5. (9.5-12s) Bottom badges
 */
export const MedicalLibrary: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // Phase tracking for content switching
  const showUpload = frame < fps * 3.5;
  const showPipeline = frame >= fps * 3.5 && frame < fps * 7;
  const showResults = frame >= fps * 7;

  return (
    <AbsoluteFill>
      <Background variant="dark" />

      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 28,
          padding: '40px 80px',
        }}
      >
        {/* Title (0-1.5s) */}
        <Sequence from={0}>
          <div
            style={{
              textAlign: 'center',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: 12,
            }}
          >
            <WordReveal
              text="Your Personal Medical Library"
              fontSize={52}
              fontWeight={700}
              delay={0}
            />
            <LineReveal
              lines={['Upload any medical document']}
              fontSize={22}
              color={COLORS.slate[400]}
              delay={10}
            />
          </div>
        </Sequence>

        {/* MockBrowser (1.5s+) */}
        <Sequence from={0} premountFor={Math.round(fps * 1.5)}>
          <MockBrowser
            delay={Math.round(fps * 1.5)}
            url="postvisit.ai/library"
          >
            <div
              style={{
                background: COLORS.slate[900],
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
              }}
            >
              {/* Header bar */}
              <div
                style={{
                  padding: '12px 24px',
                  borderBottom: `1px solid ${COLORS.slate[700]}`,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                }}
              >
                <span
                  style={{
                    fontSize: 16,
                    fontWeight: 700,
                    color: COLORS.white,
                    fontFamily: FONT.body,
                  }}
                >
                  Medical Library
                </span>
                <div
                  style={{
                    padding: '6px 14px',
                    borderRadius: 8,
                    background: COLORS.emerald[600],
                  }}
                >
                  <span
                    style={{
                      fontSize: 12,
                      fontWeight: 600,
                      color: COLORS.white,
                      fontFamily: FONT.body,
                    }}
                  >
                    Upload Document
                  </span>
                </div>
              </div>

              {/* Content area - switches between phases */}
              <div style={{flex: 1, overflow: 'hidden'}}>
                {showUpload && <UploadZone delay={Math.round(fps * 1.5)} />}

                {showPipeline && (
                  <div
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      height: '100%',
                    }}
                  >
                    {/* Compact file reference */}
                    <div
                      style={{
                        padding: '12px 24px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                        borderBottom: `1px solid ${COLORS.slate[700]}44`,
                      }}
                    >
                      <div
                        style={{
                          width: 28,
                          height: 34,
                          background: COLORS.rose[500],
                          borderRadius: 4,
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                      >
                        <span
                          style={{
                            fontSize: 8,
                            fontWeight: 800,
                            color: COLORS.white,
                            fontFamily: FONT.body,
                          }}
                        >
                          PDF
                        </span>
                      </div>
                      <div>
                        <div
                          style={{
                            fontSize: 13,
                            fontWeight: 600,
                            color: COLORS.white,
                            fontFamily: FONT.mono,
                          }}
                        >
                          WHO_CVD_Risk_Assessment.pdf
                        </div>
                        <div
                          style={{
                            fontSize: 11,
                            color: COLORS.emerald[400],
                            fontFamily: FONT.body,
                          }}
                        >
                          Analyzing...
                        </div>
                      </div>
                    </div>

                    {/* Pipeline */}
                    <div
                      style={{
                        flex: 1,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                      }}
                    >
                      <AnalysisPipeline delay={Math.round(fps * 3.5)} />
                    </div>
                  </div>
                )}

                {showResults && (
                  <div
                    style={{
                      display: 'flex',
                      flexDirection: 'column',
                      height: '100%',
                    }}
                  >
                    {/* Compact file reference with completed status */}
                    <div
                      style={{
                        padding: '12px 24px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                        borderBottom: `1px solid ${COLORS.slate[700]}44`,
                      }}
                    >
                      <div
                        style={{
                          width: 28,
                          height: 34,
                          background: COLORS.rose[500],
                          borderRadius: 4,
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                      >
                        <span
                          style={{
                            fontSize: 8,
                            fontWeight: 800,
                            color: COLORS.white,
                            fontFamily: FONT.body,
                          }}
                        >
                          PDF
                        </span>
                      </div>
                      <div style={{flex: 1}}>
                        <div
                          style={{
                            fontSize: 13,
                            fontWeight: 600,
                            color: COLORS.white,
                            fontFamily: FONT.mono,
                          }}
                        >
                          WHO_CVD_Risk_Assessment.pdf
                        </div>
                        <div
                          style={{
                            fontSize: 11,
                            color: COLORS.emerald[400],
                            fontFamily: FONT.body,
                          }}
                        >
                          Analysis complete
                        </div>
                      </div>
                      {/* Completed checkmark */}
                      <div
                        style={{
                          width: 24,
                          height: 24,
                          borderRadius: '50%',
                          background: `${COLORS.emerald[500]}33`,
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                      >
                        <svg width={14} height={14} viewBox="0 0 24 24" fill="none">
                          <path
                            d="M5 12l5 5L19 7"
                            stroke={COLORS.emerald[400]}
                            strokeWidth={3}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                          />
                        </svg>
                      </div>
                    </div>

                    {/* Results */}
                    <ResultsPanel delay={Math.round(fps * 7)} />
                  </div>
                )}
              </div>
            </div>
          </MockBrowser>
        </Sequence>

        {/* Bottom badges (9.5-12s) */}
        <Sequence from={0} premountFor={Math.round(fps * 9.5)}>
          <div style={{display: 'flex', gap: 12, justifyContent: 'center'}}>
            <Badge
              text="PubMed verified"
              variant="emerald"
              delay={Math.round(fps * 9.5)}
            />
            <Badge
              text="Auto-categorized"
              variant="sky"
              delay={Math.round(fps * 10)}
            />
            <Badge
              text="Patient-relevant insights"
              variant="violet"
              delay={Math.round(fps * 10.5)}
            />
          </div>
        </Sequence>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
