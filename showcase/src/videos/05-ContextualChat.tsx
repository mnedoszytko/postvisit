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
import {WordReveal, TypewriterText} from '../components/AnimatedText';
import {MockBrowser} from '../components/MockPhone';
import {Badge, SourceChip} from '../components/Badge';

/* ---------- sub-components ---------- */

/** A single chat bubble */
const ChatBubble: React.FC<{
  text: string;
  isUser: boolean;
  delay: number;
  children?: React.ReactNode;
}> = ({text, isUser, delay, children}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const translateY = interpolate(appear, [0, 1], [16, 0], {
    extrapolateRight: 'clamp',
  });

  return (
    <div
      style={{
        display: 'flex',
        justifyContent: isUser ? 'flex-end' : 'flex-start',
        opacity: appear,
        transform: `translateY(${translateY}px)`,
        marginBottom: 12,
      }}
    >
      <div
        style={{
          maxWidth: '75%',
          background: isUser
            ? `linear-gradient(135deg, ${COLORS.emerald[600]}, ${COLORS.emerald[500]})`
            : COLORS.slate[700],
          borderRadius: 14,
          borderBottomRightRadius: isUser ? 4 : 14,
          borderBottomLeftRadius: isUser ? 14 : 4,
          padding: '10px 14px',
        }}
      >
        <div
          style={{
            fontSize: 13,
            color: isUser ? COLORS.white : COLORS.slate[200],
            lineHeight: 1.6,
          }}
        >
          {text}
        </div>
        {children}
      </div>
    </div>
  );
};

/** A chat bubble with typewriter text reveal */
const TypewriterBubble: React.FC<{
  text: string;
  isUser: boolean;
  delay: number;
  speed?: number;
  children?: React.ReactNode;
}> = ({text, isUser, delay, speed = 1.5, children}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const translateY = interpolate(appear, [0, 1], [16, 0], {
    extrapolateRight: 'clamp',
  });

  return (
    <div
      style={{
        display: 'flex',
        justifyContent: isUser ? 'flex-end' : 'flex-start',
        opacity: appear,
        transform: `translateY(${translateY}px)`,
        marginBottom: 12,
      }}
    >
      <div
        style={{
          maxWidth: '75%',
          background: isUser
            ? `linear-gradient(135deg, ${COLORS.emerald[600]}, ${COLORS.emerald[500]})`
            : COLORS.slate[700],
          borderRadius: 14,
          borderBottomRightRadius: isUser ? 4 : 14,
          borderBottomLeftRadius: isUser ? 14 : 4,
          padding: '10px 14px',
        }}
      >
        <TypewriterText
          text={text}
          fontSize={13}
          color={isUser ? COLORS.white : COLORS.slate[200]}
          delay={delay + 5}
          speed={speed}
        />
        {children}
      </div>
    </div>
  );
};

/** Deep Analysis indicator with progress bar */
const DeepAnalysisIndicator: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({
    frame,
    fps,
    delay,
    config: {damping: 16, stiffness: 120},
  });

  const analysisDuration = fps * 2.5; // 2.5 seconds of analysis
  const analysisProgress = interpolate(
    frame - delay,
    [0, analysisDuration],
    [0, 100],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  );

  const tokenCount = Math.floor(
    interpolate(frame - delay, [0, analysisDuration], [0, 847], {
      extrapolateLeft: 'clamp',
      extrapolateRight: 'clamp',
    }),
  );

  const elapsed = interpolate(
    frame - delay,
    [0, analysisDuration],
    [0, 3.2],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  ).toFixed(1);

  const sparkleRotate = interpolate(frame, [0, fps * 4], [0, 360]);

  const translateY = interpolate(appear, [0, 1], [12, 0], {
    extrapolateRight: 'clamp',
  });

  return (
    <div
      style={{
        display: 'flex',
        justifyContent: 'flex-start',
        opacity: appear,
        transform: `translateY(${translateY}px)`,
        marginBottom: 12,
      }}
    >
      <div
        style={{
          maxWidth: '75%',
          background: `linear-gradient(135deg, ${COLORS.slate[800]}, ${COLORS.slate[700]})`,
          border: `1px solid ${COLORS.emerald[500]}33`,
          borderRadius: 14,
          padding: 16,
        }}
      >
        {/* Header row */}
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 8,
            marginBottom: 10,
          }}
        >
          {/* Diamond sparkle icon */}
          <div
            style={{
              transform: `rotate(${sparkleRotate}deg)`,
              width: 16,
              height: 16,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <svg width={14} height={14} viewBox="0 0 14 14" fill="none">
              <path
                d="M7 0L8.5 5.5L14 7L8.5 8.5L7 14L5.5 8.5L0 7L5.5 5.5L7 0Z"
                fill={COLORS.emerald[400]}
              />
            </svg>
          </div>
          <span
            style={{
              fontSize: 13,
              fontWeight: 600,
              color: COLORS.emerald[300],
            }}
          >
            Deep Analysis
          </span>
        </div>

        {/* Status text */}
        <div
          style={{
            fontSize: 12,
            color: COLORS.slate[400],
            marginBottom: 8,
          }}
        >
          Analyzing drug interactions...
        </div>

        {/* Stats row */}
        <div
          style={{
            display: 'flex',
            gap: 16,
            marginBottom: 10,
            fontSize: 11,
            fontFamily: FONT.mono,
          }}
        >
          <span style={{color: COLORS.slate[400]}}>
            <span style={{color: COLORS.emerald[400]}}>{tokenCount}</span>{' '}
            thinking tokens
          </span>
          <span style={{color: COLORS.slate[400]}}>
            <span style={{color: COLORS.amber[400]}}>{elapsed}s</span>{' '}
            elapsed
          </span>
        </div>

        {/* Progress bar */}
        <div
          style={{
            width: '100%',
            height: 4,
            borderRadius: 2,
            background: COLORS.slate[600],
            overflow: 'hidden',
          }}
        >
          <div
            style={{
              width: `${analysisProgress}%`,
              height: '100%',
              borderRadius: 2,
              background: `linear-gradient(90deg, ${COLORS.emerald[500]}, ${COLORS.emerald[400]})`,
              boxShadow: `0 0 8px ${COLORS.emerald[500]}66`,
            }}
          />
        </div>
      </div>
    </div>
  );
};

/** Full response with streaming typewriter */
const StreamingResponse: React.FC<{delay: number}> = ({delay}) => {
  const responseText =
    'Based on your current medications, taking ibuprofen with propranolol requires caution. ' +
    'NSAIDs can reduce the blood pressure-lowering effect of beta-blockers. ' +
    'Consider acetaminophen as a safer alternative for pain relief.';

  return (
    <TypewriterBubble
      text={responseText}
      isUser={false}
      delay={delay}
      speed={3}
    />
  );
};

/* ---------- main composition ---------- */

export const ContextualChat: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const titleStart = 0;
  const chatAppearStart = Math.round(fps * 1.5);
  const userTypeStart = Math.round(fps * 3);
  const quickResponseStart = Math.round(fps * 5);
  const deepAnalysisStart = Math.round(fps * 6.5);
  const fullResponseStart = Math.round(fps * 9);
  const sourcesStart = Math.round(fps * 12);
  const badgeStart = Math.round(fps * 14);

  // Title fade out
  const titleFade = interpolate(
    frame,
    [chatAppearStart - 5, chatAppearStart + 10],
    [1, 0],
    {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
  );

  // Browser slide up
  const browserSlide = spring({
    frame,
    fps,
    delay: chatAppearStart,
    config: {damping: 22, stiffness: 100},
  });
  const browserY = interpolate(browserSlide, [0, 1], [60, 0], {
    extrapolateRight: 'clamp',
  });

  return (
    <AbsoluteFill>
      <Background variant="dark" />

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
          <Logo size={36} showBadge={false} delay={0} />
        </div>

        {/* Title */}
        <div style={{opacity: titleFade, textAlign: 'center', marginBottom: 16}}>
          <WordReveal
            text="AI That Thinks Before It Answers"
            fontSize={48}
            delay={5}
          />
        </div>

        {/* Browser with chat */}
        <div
          style={{
            transform: `translateY(${browserY}px)`,
            opacity: browserSlide,
          }}
        >
          <MockBrowser delay={chatAppearStart} url="postvisit.ai/chat">
            <div
              style={{
                display: 'flex',
                flexDirection: 'column',
                height: '100%',
                padding: 20,
              }}
            >
              {/* Chat header */}
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 10,
                  paddingBottom: 14,
                  borderBottom: `1px solid ${COLORS.slate[700]}`,
                  marginBottom: 16,
                }}
              >
                <div
                  style={{
                    width: 32,
                    height: 32,
                    borderRadius: 16,
                    background: `linear-gradient(135deg, ${COLORS.emerald[500]}, ${COLORS.emerald[600]})`,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  <svg width={16} height={16} viewBox="0 0 24 24" fill="none">
                    <path
                      d="M12 2L2 7v10l10 5 10-5V7L12 2z"
                      stroke="white"
                      strokeWidth="2"
                      fill="none"
                    />
                  </svg>
                </div>
                <div>
                  <div
                    style={{fontSize: 15, fontWeight: 600, color: COLORS.white}}
                  >
                    PostVisit AI
                  </div>
                  <div
                    style={{fontSize: 11, color: COLORS.emerald[400]}}
                  >
                    Visit context loaded
                  </div>
                </div>
              </div>

              {/* Scrollable messages area */}
              <div
                style={{
                  flex: 1,
                  display: 'flex',
                  flexDirection: 'column',
                  overflow: 'hidden',
                }}
              >
                {/* Existing context messages */}
                <ChatBubble
                  text="Your visit with Dr. Smith on Feb 12 has been loaded. I have access to your visit notes, medications, and lab results."
                  isUser={false}
                  delay={chatAppearStart + 5}
                />
                <ChatBubble
                  text="What are my medications for?"
                  isUser={true}
                  delay={chatAppearStart + 12}
                />
                <ChatBubble
                  text="You were prescribed Propranolol 40mg twice daily to manage your premature ventricular complexes (PVCs) and reduce heart palpitations."
                  isUser={false}
                  delay={chatAppearStart + 18}
                />

                {/* User types new question */}
                <Sequence from={userTypeStart} premountFor={15}>
                  <TypewriterBubble
                    text="Can I take ibuprofen with my propranolol?"
                    isUser={true}
                    delay={userTypeStart}
                    speed={2}
                  />
                </Sequence>

                {/* Quick response */}
                <Sequence from={quickResponseStart} premountFor={15}>
                  <ChatBubble
                    text="Let me check that for you. Running a deeper analysis now..."
                    isUser={false}
                    delay={quickResponseStart}
                  />
                </Sequence>

                {/* Deep analysis indicator */}
                <Sequence from={deepAnalysisStart} premountFor={15}>
                  <DeepAnalysisIndicator delay={deepAnalysisStart} />
                </Sequence>

                {/* Full streaming response */}
                <Sequence from={fullResponseStart} premountFor={15}>
                  <StreamingResponse delay={fullResponseStart} />
                </Sequence>

                {/* Source chips */}
                <Sequence from={sourcesStart} premountFor={15}>
                  {(() => {
                    const chipsAppear = spring({
                      frame,
                      fps,
                      delay: sourcesStart,
                      config: {damping: 200},
                    });
                    return (
                      <div
                        style={{
                          display: 'flex',
                          gap: 8,
                          marginLeft: 8,
                          opacity: chipsAppear,
                          transform: `translateY(${interpolate(chipsAppear, [0, 1], [8, 0])}px)`,
                        }}
                      >
                        <SourceChip
                          label="Visit Notes"
                          variant="sky"
                          delay={sourcesStart}
                        />
                        <SourceChip
                          label="OpenFDA"
                          variant="amber"
                          delay={sourcesStart + 4}
                        />
                        <SourceChip
                          label="ESC Guidelines"
                          variant="violet"
                          delay={sourcesStart + 8}
                        />
                      </div>
                    );
                  })()}
                </Sequence>
              </div>
            </div>
          </MockBrowser>
        </div>

        {/* Bottom badge */}
        <div style={{marginTop: 24}}>
          <Badge
            text="Every answer backed by sources"
            variant="emerald"
            delay={badgeStart}
          />
        </div>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
