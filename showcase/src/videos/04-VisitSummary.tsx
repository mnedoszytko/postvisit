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
import {MockBrowser} from '../components/MockPhone';
import {SourceChip} from '../components/Badge';

/* ---------- sub-components ---------- */

/** Gray placeholder bar for mock text lines */
const TextBar: React.FC<{
  width: number;
  delay: number;
  color?: string;
}> = ({width, delay, color = COLORS.slate[700]}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});

  return (
    <div
      style={{
        width,
        height: 10,
        borderRadius: 5,
        background: color,
        opacity: interpolate(appear, [0, 1], [0, 0.6]),
        transform: `scaleX(${interpolate(appear, [0, 1], [0.6, 1])})`,
        transformOrigin: 'left',
      }}
    />
  );
};

/** Section header inside the SOAP note */
const SoapSection: React.FC<{
  title: string;
  delay: number;
  children: React.ReactNode;
}> = ({title, delay, children}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});

  return (
    <div
      style={{
        opacity: appear,
        transform: `translateY(${interpolate(appear, [0, 1], [12, 0])}px)`,
        marginBottom: 16,
      }}
    >
      <div
        style={{
          fontSize: 13,
          fontWeight: 700,
          color: COLORS.emerald[400],
          letterSpacing: '0.08em',
          textTransform: 'uppercase' as const,
          marginBottom: 8,
          fontFamily: FONT.mono,
        }}
      >
        {title}
      </div>
      <div style={{display: 'flex', flexDirection: 'column', gap: 6}}>
        {children}
      </div>
    </div>
  );
};

/** A medical term that highlights with emerald underline */
const MedicalTerm: React.FC<{
  text: string;
  highlightDelay: number;
}> = ({text, highlightDelay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const highlight = spring({
    frame,
    fps,
    delay: highlightDelay,
    config: {damping: 18, stiffness: 120},
  });

  const underlineWidth = interpolate(highlight, [0, 1], [0, 100], {
    extrapolateRight: 'clamp',
  });
  const glowOpacity = interpolate(highlight, [0, 1], [0, 0.4], {
    extrapolateRight: 'clamp',
  });
  const textColor = interpolate(highlight, [0, 1], [0, 1]);

  return (
    <span
      style={{
        display: 'inline-block',
        position: 'relative',
        fontSize: 13,
        fontWeight: 500,
        color:
          textColor > 0.5 ? COLORS.emerald[300] : COLORS.slate[300],
        padding: '1px 3px',
      }}
    >
      {text}
      {/* Underline */}
      <span
        style={{
          position: 'absolute',
          bottom: -1,
          left: 0,
          width: `${underlineWidth}%`,
          height: 2,
          background: COLORS.emerald[400],
          borderRadius: 1,
        }}
      />
      {/* Glow */}
      <span
        style={{
          position: 'absolute',
          inset: -2,
          borderRadius: 4,
          background: COLORS.emerald[500],
          opacity: glowOpacity,
          filter: 'blur(6px)',
          zIndex: -1,
        }}
      />
    </span>
  );
};

/** Tap-to-explain popover */
const ExplainPopover: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({
    frame,
    fps,
    delay,
    config: {damping: 14, stiffness: 160},
  });

  const scale = interpolate(appear, [0, 1], [0.8, 1], {
    extrapolateRight: 'clamp',
  });

  return (
    <div
      style={{
        position: 'absolute',
        top: 140,
        left: 240,
        width: 260,
        background: COLORS.slate[800],
        border: `1px solid ${COLORS.emerald[500]}44`,
        borderRadius: 10,
        padding: 14,
        opacity: appear,
        transform: `scale(${scale})`,
        transformOrigin: 'top left',
        boxShadow: `0 8px 30px ${COLORS.slate[950]}cc, 0 0 20px ${COLORS.emerald[500]}11`,
        zIndex: 10,
      }}
    >
      {/* Arrow */}
      <div
        style={{
          position: 'absolute',
          top: -6,
          left: 30,
          width: 12,
          height: 12,
          background: COLORS.slate[800],
          border: `1px solid ${COLORS.emerald[500]}44`,
          borderRight: 'none',
          borderBottom: 'none',
          transform: 'rotate(45deg)',
        }}
      />
      <div
        style={{
          fontSize: 14,
          fontWeight: 600,
          color: COLORS.emerald[300],
          marginBottom: 6,
        }}
      >
        Beta-blocker medication
      </div>
      <div
        style={{
          fontSize: 12,
          color: COLORS.slate[300],
          lineHeight: 1.5,
          marginBottom: 10,
        }}
      >
        Slows heart rate to reduce PVCs
      </div>
      <div
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: 4,
          padding: '4px 10px',
          borderRadius: 6,
          background: `${COLORS.emerald[500]}22`,
          border: `1px solid ${COLORS.emerald[500]}33`,
        }}
      >
        <span style={{fontSize: 11, color: COLORS.emerald[400], fontWeight: 500}}>
          Ask AI for more
        </span>
        <span style={{fontSize: 11, color: COLORS.emerald[400]}}>{'>'}</span>
      </div>
    </div>
  );
};

/** AI chat panel that slides in from the right */
const ChatPanel: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const slideIn = spring({
    frame,
    fps,
    delay,
    config: {damping: 18, stiffness: 100},
  });

  const translateX = interpolate(slideIn, [0, 1], [200, 0], {
    extrapolateRight: 'clamp',
  });

  return (
    <div
      style={{
        position: 'absolute',
        top: 0,
        right: 0,
        width: 400,
        height: '100%',
        background: COLORS.slate[800],
        borderLeft: `1px solid ${COLORS.slate[700]}`,
        opacity: slideIn,
        transform: `translateX(${translateX}px)`,
        display: 'flex',
        flexDirection: 'column',
        padding: 20,
      }}
    >
      {/* Chat header */}
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: 8,
          marginBottom: 20,
          paddingBottom: 12,
          borderBottom: `1px solid ${COLORS.slate[700]}`,
        }}
      >
        <div
          style={{
            width: 28,
            height: 28,
            borderRadius: 14,
            background: `linear-gradient(135deg, ${COLORS.emerald[500]}, ${COLORS.emerald[600]})`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <svg width={14} height={14} viewBox="0 0 24 24" fill="none">
            <path
              d="M12 2L2 7v10l10 5 10-5V7L12 2z"
              stroke="white"
              strokeWidth="2"
              fill="none"
            />
          </svg>
        </div>
        <div style={{fontSize: 14, fontWeight: 600, color: COLORS.white}}>
          PostVisit AI
        </div>
      </div>

      {/* AI message */}
      <div
        style={{
          background: COLORS.slate[700],
          borderRadius: 12,
          padding: 14,
          marginBottom: 12,
        }}
      >
        <div style={{fontSize: 12, color: COLORS.slate[300], lineHeight: 1.6}}>
          Propranolol is a non-selective beta-blocker prescribed to manage your
          premature ventricular complexes. It works by slowing your heart rate
          and reducing the heart's workload.
        </div>
      </div>
    </div>
  );
};

/** Source chips row with staggered appearance */
const SourceChipsRow: React.FC<{delay: number}> = ({delay}) => {
  const chips: Array<{label: string; variant: 'emerald' | 'amber' | 'violet' | 'sky'}> = [
    {label: 'Visit Notes', variant: 'sky'},
    {label: 'FDA Data', variant: 'amber'},
    {label: 'Guidelines', variant: 'violet'},
  ];

  return (
    <div style={{display: 'flex', gap: 8, marginTop: 8}}>
      {chips.map((chip, i) => (
        <SourceChip
          key={chip.label}
          label={chip.label}
          variant={chip.variant}
          delay={delay + i * 4}
        />
      ))}
    </div>
  );
};

/* ---------- main composition ---------- */

export const VisitSummary: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const titleStart = 0;
  const browserStart = Math.round(fps * 1.5);
  const highlightStart = Math.round(fps * 4);
  const popoverStart = Math.round(fps * 6);
  const chatStart = Math.round(fps * 8);
  const sourcesStart = Math.round(fps * 10);

  return (
    <AbsoluteFill>
      <Background variant="dark" />

      {/* Title sequence */}
      <Sequence from={titleStart} durationInFrames={Math.round(fps * 12)} premountFor={0}>
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
            <Logo size={36} showBadge={false} delay={titleStart} />
          </div>

          {/* Title - fades out when browser comes in */}
          {(() => {
            const titleFade = interpolate(
              frame,
              [browserStart - 5, browserStart + 10],
              [1, 0],
              {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
            );
            return (
              <div
                style={{
                  opacity: titleFade,
                  textAlign: 'center',
                  marginBottom: 16,
                }}
              >
                <WordReveal
                  text="Your Visit Summary"
                  fontSize={52}
                  delay={titleStart + 5}
                />
                <div style={{marginTop: 12}}>
                  <WordReveal
                    text="Everything from your appointment, explained"
                    fontSize={22}
                    color={COLORS.slate[400]}
                    fontWeight={400}
                    delay={titleStart + 15}
                  />
                </div>
              </div>
            );
          })()}

          {/* Browser mock */}
          {(() => {
            const browserSlide = spring({
              frame,
              fps,
              delay: browserStart,
              config: {damping: 22, stiffness: 100},
            });
            const yOffset = interpolate(browserSlide, [0, 1], [80, 20], {
              extrapolateRight: 'clamp',
            });
            return (
              <div
                style={{
                  transform: `translateY(${yOffset}px)`,
                  opacity: browserSlide,
                }}
              >
                <MockBrowser delay={browserStart} url="postvisit.ai/visit/summary">
                  <div
                    style={{
                      display: 'flex',
                      height: '100%',
                      position: 'relative',
                    }}
                  >
                    {/* Left panel: SOAP notes */}
                    <div
                      style={{
                        flex: 1,
                        padding: 24,
                        position: 'relative',
                        overflow: 'hidden',
                      }}
                    >
                      {/* SOAP header */}
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          gap: 8,
                          marginBottom: 20,
                        }}
                      >
                        <div
                          style={{
                            width: 8,
                            height: 8,
                            borderRadius: '50%',
                            background: COLORS.emerald[400],
                          }}
                        />
                        <span
                          style={{
                            fontSize: 16,
                            fontWeight: 600,
                            color: COLORS.white,
                          }}
                        >
                          Visit Notes
                        </span>
                        <span
                          style={{
                            fontSize: 11,
                            color: COLORS.slate[500],
                            marginLeft: 'auto',
                          }}
                        >
                          Feb 12, 2026
                        </span>
                      </div>

                      {/* Chief Complaint */}
                      <SoapSection title="Chief Complaint" delay={browserStart + 8}>
                        <div
                          style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: 4,
                            alignItems: 'center',
                          }}
                        >
                          <TextBar width={80} delay={browserStart + 10} />
                          <MedicalTerm
                            text="Premature Ventricular Complexes"
                            highlightDelay={highlightStart}
                          />
                          <TextBar width={140} delay={browserStart + 12} />
                        </div>
                        <TextBar width={320} delay={browserStart + 14} />
                      </SoapSection>

                      {/* Assessment */}
                      <SoapSection title="Assessment" delay={browserStart + 16}>
                        <TextBar width={200} delay={browserStart + 18} />
                        <div
                          style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: 4,
                            alignItems: 'center',
                          }}
                        >
                          <TextBar width={60} delay={browserStart + 20} />
                          <MedicalTerm
                            text="Echocardiogram"
                            highlightDelay={highlightStart + 12}
                          />
                          <TextBar width={120} delay={browserStart + 22} />
                        </div>
                        <TextBar width={280} delay={browserStart + 24} />
                      </SoapSection>

                      {/* Plan */}
                      <SoapSection title="Plan" delay={browserStart + 26}>
                        <div
                          style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: 4,
                            alignItems: 'center',
                          }}
                        >
                          <TextBar width={40} delay={browserStart + 28} />
                          <MedicalTerm
                            text="Propranolol 40mg"
                            highlightDelay={highlightStart + 6}
                          />
                          <TextBar width={100} delay={browserStart + 30} />
                        </div>
                        <TextBar width={260} delay={browserStart + 32} />
                        <TextBar width={180} delay={browserStart + 34} />
                      </SoapSection>

                      {/* Popover */}
                      <ExplainPopover delay={popoverStart} />
                    </div>

                    {/* Right panel: AI chat (slides in) */}
                    <ChatPanel delay={chatStart} />
                  </div>
                </MockBrowser>
              </div>
            );
          })()}

          {/* Source chips below browser */}
          <div style={{marginTop: 20}}>
            <SourceChipsRow delay={sourcesStart} />
          </div>
        </AbsoluteFill>
      </Sequence>
    </AbsoluteFill>
  );
};
