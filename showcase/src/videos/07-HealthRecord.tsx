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

// --- Tab Data ---

const TABS = ['Profile', 'Vitals', 'Labs', 'Connected', 'Documents'] as const;

type TabName = (typeof TABS)[number];

const TAB_COLORS: Record<TabName, string> = {
  Profile: COLORS.slate[400],
  Vitals: COLORS.emerald[400],
  Labs: COLORS.sky[400],
  Connected: COLORS.violet[400],
  Documents: COLORS.amber[400],
};

// --- Heart rate SVG data ---

const HR_POINTS = [
  0, 72, 68, 75, 73, 69, 78, 74, 71, 76, 73, 70, 77, 72, 68, 75, 80, 74, 71,
  73, 76, 72, 69, 74, 72, 70, 75, 73, 71, 74,
];

function buildHeartRatePath(
  points: number[],
  width: number,
  height: number,
): string {
  const minVal = Math.min(...points);
  const maxVal = Math.max(...points);
  const range = maxVal - minVal || 1;
  const stepX = width / (points.length - 1);
  const padding = 10;

  return points
    .map((val, i) => {
      const x = i * stepX;
      const y = padding + (1 - (val - minVal) / range) * (height - padding * 2);
      return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
    })
    .join(' ');
}

// --- Lab Result Row ---

const LabRow: React.FC<{
  name: string;
  value: string;
  unit: string;
  min: number;
  max: number;
  actual: number;
  delay: number;
}> = ({name, value, unit, min, max, actual, delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const barWidth = interpolate(appear, [0, 1], [0, 100], {
    extrapolateRight: 'clamp',
  });

  const range = max - min;
  const pos = ((actual - min) / range) * 100;

  return (
    <div
      style={{
        opacity: appear,
        transform: `translateX(${interpolate(appear, [0, 1], [40, 0])}px)`,
        display: 'flex',
        alignItems: 'center',
        gap: 16,
        padding: '10px 20px',
        borderBottom: `1px solid ${COLORS.slate[700]}44`,
      }}
    >
      <span
        style={{
          width: 120,
          fontSize: 15,
          fontWeight: 600,
          color: COLORS.slate[200],
          fontFamily: FONT.body,
        }}
      >
        {name}
      </span>
      <span
        style={{
          width: 80,
          fontSize: 16,
          fontWeight: 700,
          color: COLORS.white,
          fontFamily: FONT.mono,
        }}
      >
        {value}
      </span>
      <span
        style={{
          width: 60,
          fontSize: 12,
          color: COLORS.slate[400],
          fontFamily: FONT.body,
        }}
      >
        {unit}
      </span>
      {/* Reference range bar */}
      <div
        style={{
          flex: 1,
          height: 8,
          borderRadius: 4,
          background: COLORS.slate[700],
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        <div
          style={{
            position: 'absolute',
            left: '10%',
            right: '10%',
            top: 0,
            bottom: 0,
            background: `${COLORS.emerald[500]}33`,
            borderRadius: 4,
          }}
        />
        <div
          style={{
            position: 'absolute',
            left: `${Math.min(Math.max(pos * (barWidth / 100), 0), 95)}%`,
            top: -2,
            width: 12,
            height: 12,
            borderRadius: '50%',
            background: COLORS.emerald[400],
            boxShadow: `0 0 8px ${COLORS.emerald[500]}66`,
          }}
        />
      </div>
      {/* Status badge */}
      <div
        style={{
          padding: '3px 10px',
          borderRadius: 10,
          background: `${COLORS.emerald[500]}22`,
          border: `1px solid ${COLORS.emerald[500]}44`,
        }}
      >
        <span
          style={{
            fontSize: 11,
            fontWeight: 600,
            color: COLORS.emerald[300],
            fontFamily: FONT.body,
          }}
        >
          Normal
        </span>
      </div>
    </div>
  );
};

// --- Tab Bar Component ---

const TabBar: React.FC<{
  activeTab: TabName;
  highlightProgress: number;
}> = ({activeTab, highlightProgress}) => {
  const tabIndex = TABS.indexOf(activeTab);

  return (
    <div
      style={{
        display: 'flex',
        gap: 0,
        borderBottom: `1px solid ${COLORS.slate[700]}`,
        padding: '0 20px',
      }}
    >
      {TABS.map((tab, i) => {
        const isActive = tab === activeTab;
        const wasHighlighted = i <= tabIndex;

        return (
          <div
            key={tab}
            style={{
              padding: '12px 24px',
              borderBottom: isActive
                ? `2px solid ${TAB_COLORS[tab]}`
                : wasHighlighted
                  ? `2px solid ${COLORS.slate[600]}`
                  : `2px solid transparent`,
              opacity: wasHighlighted
                ? 1
                : interpolate(highlightProgress, [0, 1], [0.4, 0.7], {
                    extrapolateRight: 'clamp',
                  }),
            }}
          >
            <span
              style={{
                fontSize: 14,
                fontWeight: isActive ? 700 : 500,
                color: isActive
                  ? TAB_COLORS[tab]
                  : wasHighlighted
                    ? COLORS.slate[300]
                    : COLORS.slate[500],
                fontFamily: FONT.body,
              }}
            >
              {tab}
            </span>
          </div>
        );
      })}
    </div>
  );
};

// --- Vitals Panel ---

const VitalsPanel: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const chartAppear = spring({
    frame,
    fps,
    delay,
    config: {damping: 200},
  });

  const chartWidth = 500;
  const chartHeight = 120;
  const path = buildHeartRatePath(HR_POINTS, chartWidth, chartHeight);

  // Calculate path length approximation for stroke-dasharray
  const pathLength = 1200;
  const dashOffset = interpolate(chartAppear, [0, 1], [pathLength, 0], {
    extrapolateRight: 'clamp',
  });

  const bpAppear = spring({
    frame,
    fps,
    delay: delay + 15,
    config: {damping: 200},
  });

  return (
    <div style={{padding: 24, display: 'flex', gap: 24}}>
      {/* Heart Rate Chart */}
      <div
        style={{
          flex: 1,
          background: COLORS.slate[800],
          borderRadius: 12,
          padding: 20,
          border: `1px solid ${COLORS.slate[700]}`,
        }}
      >
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: 16,
          }}
        >
          <span
            style={{
              fontSize: 14,
              fontWeight: 600,
              color: COLORS.slate[300],
              fontFamily: FONT.body,
            }}
          >
            Heart Rate (7 days)
          </span>
          <div
            style={{
              opacity: chartAppear,
              display: 'flex',
              alignItems: 'baseline',
              gap: 4,
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
              72
            </span>
            <span
              style={{
                fontSize: 13,
                color: COLORS.slate[400],
                fontFamily: FONT.body,
              }}
            >
              BPM avg
            </span>
          </div>
        </div>
        <svg
          width={chartWidth}
          height={chartHeight}
          viewBox={`0 0 ${chartWidth} ${chartHeight}`}
          style={{overflow: 'visible'}}
        >
          {/* Grid lines */}
          {[0.25, 0.5, 0.75].map((ratio) => (
            <line
              key={ratio}
              x1={0}
              y1={chartHeight * ratio}
              x2={chartWidth}
              y2={chartHeight * ratio}
              stroke={COLORS.slate[700]}
              strokeWidth={1}
              strokeDasharray="4 4"
            />
          ))}
          {/* Gradient fill area */}
          <defs>
            <linearGradient id="hrGradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={COLORS.emerald[400]} stopOpacity={0.3} />
              <stop offset="100%" stopColor={COLORS.emerald[400]} stopOpacity={0} />
            </linearGradient>
          </defs>
          {/* Line */}
          <path
            d={path}
            fill="none"
            stroke={COLORS.emerald[400]}
            strokeWidth={2.5}
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeDasharray={pathLength}
            strokeDashoffset={dashOffset}
          />
        </svg>
      </div>

      {/* Blood Pressure Card */}
      <div
        style={{
          width: 220,
          background: COLORS.slate[800],
          borderRadius: 12,
          padding: 20,
          border: `1px solid ${COLORS.slate[700]}`,
          opacity: bpAppear,
          transform: `translateY(${interpolate(bpAppear, [0, 1], [20, 0])}px)`,
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'center',
          gap: 12,
        }}
      >
        <span
          style={{
            fontSize: 14,
            fontWeight: 600,
            color: COLORS.slate[300],
            fontFamily: FONT.body,
          }}
        >
          Blood Pressure
        </span>
        <div style={{display: 'flex', alignItems: 'baseline', gap: 6}}>
          <span
            style={{
              fontSize: 32,
              fontWeight: 700,
              color: COLORS.white,
              fontFamily: FONT.mono,
            }}
          >
            120/78
          </span>
          <span
            style={{
              fontSize: 13,
              color: COLORS.slate[400],
              fontFamily: FONT.body,
            }}
          >
            mmHg
          </span>
        </div>
        {/* Trend arrow */}
        <div style={{display: 'flex', alignItems: 'center', gap: 6}}>
          <svg width={16} height={16} viewBox="0 0 16 16">
            <path
              d="M8 12V4M8 4L5 7M8 4L11 7"
              stroke={COLORS.emerald[400]}
              strokeWidth={2}
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
          <span
            style={{
              fontSize: 13,
              color: COLORS.emerald[400],
              fontWeight: 600,
              fontFamily: FONT.body,
            }}
          >
            Improved from 135/85
          </span>
        </div>
      </div>
    </div>
  );
};

// --- Connected Services Panel ---

const ConnectedPanel: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const cardAppear = spring({
    frame,
    fps,
    delay,
    config: {damping: 200},
  });

  // Data flowing dots
  const dotCount = 5;
  const dotsActive = frame > delay + 10;

  return (
    <div style={{padding: 24, display: 'flex', gap: 24}}>
      {/* Apple Watch Card */}
      <div
        style={{
          width: 280,
          background: COLORS.slate[800],
          borderRadius: 12,
          padding: 24,
          border: `1px solid ${COLORS.slate[700]}`,
          opacity: cardAppear,
          transform: `scale(${interpolate(cardAppear, [0, 1], [0.9, 1])})`,
          display: 'flex',
          flexDirection: 'column',
          gap: 16,
        }}
      >
        <div style={{display: 'flex', alignItems: 'center', gap: 12}}>
          {/* Watch icon placeholder */}
          <div
            style={{
              width: 48,
              height: 48,
              borderRadius: 12,
              background: `linear-gradient(135deg, ${COLORS.emerald[600]}, ${COLORS.emerald[500]})`,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              boxShadow: `0 0 20px ${COLORS.emerald[500]}44`,
            }}
          >
            <svg width={28} height={28} viewBox="0 0 24 24" fill="none">
              {/* Simplified watch shape */}
              <rect
                x={6}
                y={2}
                width={12}
                height={4}
                rx={2}
                fill={COLORS.white}
                opacity={0.8}
              />
              <rect
                x={4}
                y={6}
                width={16}
                height={12}
                rx={4}
                stroke={COLORS.white}
                strokeWidth={1.5}
                fill="none"
              />
              <rect
                x={6}
                y={18}
                width={12}
                height={4}
                rx={2}
                fill={COLORS.white}
                opacity={0.8}
              />
              {/* Heart on screen */}
              <path
                d="M12 14.5l-2.5-2.5a1.5 1.5 0 0 1 2.5-1.5 1.5 1.5 0 0 1 2.5 1.5L12 14.5z"
                fill={COLORS.emerald[300]}
              />
            </svg>
          </div>
          <div>
            <div
              style={{
                fontSize: 16,
                fontWeight: 700,
                color: COLORS.white,
                fontFamily: FONT.body,
              }}
            >
              Apple Watch
            </div>
            <div
              style={{
                fontSize: 12,
                color: COLORS.emerald[400],
                fontWeight: 600,
                fontFamily: FONT.body,
              }}
            >
              Connected
            </div>
          </div>
        </div>
        <div
          style={{
            fontSize: 13,
            color: COLORS.slate[400],
            fontFamily: FONT.body,
          }}
        >
          Last sync: 2 min ago
        </div>
        {/* Metrics */}
        <div
          style={{
            display: 'flex',
            gap: 8,
            flexWrap: 'wrap',
          }}
        >
          {['Heart Rate', 'ECG', 'Activity', 'Sleep'].map((metric) => (
            <div
              key={metric}
              style={{
                padding: '4px 10px',
                borderRadius: 8,
                background: `${COLORS.emerald[500]}15`,
                border: `1px solid ${COLORS.emerald[500]}33`,
              }}
            >
              <span
                style={{
                  fontSize: 11,
                  color: COLORS.emerald[300],
                  fontWeight: 500,
                  fontFamily: FONT.body,
                }}
              >
                {metric}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Data flow animation */}
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          width: 120,
          position: 'relative',
        }}
      >
        {/* Connecting line */}
        <div
          style={{
            position: 'absolute',
            top: '50%',
            left: 0,
            right: 0,
            height: 2,
            background: `${COLORS.emerald[500]}33`,
            borderRadius: 1,
          }}
        />
        {/* Flowing dots */}
        {dotsActive &&
          Array.from({length: dotCount}).map((_, i) => {
            const dotFrame = frame - delay - 10;
            const period = 30;
            const phase = (i / dotCount) * period;
            const progress = ((dotFrame + phase) % period) / period;

            return (
              <div
                key={i}
                style={{
                  position: 'absolute',
                  left: `${progress * 100}%`,
                  top: '50%',
                  transform: 'translate(-50%, -50%)',
                  width: 6,
                  height: 6,
                  borderRadius: '50%',
                  background: COLORS.emerald[400],
                  boxShadow: `0 0 8px ${COLORS.emerald[400]}`,
                  opacity: interpolate(
                    progress,
                    [0, 0.2, 0.8, 1],
                    [0, 1, 1, 0],
                  ),
                }}
              />
            );
          })}
      </div>

      {/* Dashboard data card */}
      <div
        style={{
          flex: 1,
          background: COLORS.slate[800],
          borderRadius: 12,
          padding: 24,
          border: `1px solid ${COLORS.slate[700]}`,
          opacity: cardAppear,
          display: 'flex',
          flexDirection: 'column',
          gap: 12,
        }}
      >
        <span
          style={{
            fontSize: 14,
            fontWeight: 600,
            color: COLORS.slate[300],
            fontFamily: FONT.body,
          }}
        >
          Synced Health Data
        </span>
        {[
          {label: 'Heart Rate', value: '72 BPM', trend: 'Stable'},
          {label: 'PVC Events', value: '12 today', trend: 'Decreasing'},
          {label: 'Activity', value: '6,847 steps', trend: 'Active'},
          {label: 'Sleep', value: '7h 23m', trend: 'Good'},
        ].map((item, i) => {
          const rowAppear = spring({
            frame,
            fps,
            delay: delay + 10 + i * 5,
            config: {damping: 200},
          });

          return (
            <div
              key={item.label}
              style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                padding: '8px 0',
                borderBottom:
                  i < 3 ? `1px solid ${COLORS.slate[700]}44` : 'none',
                opacity: rowAppear,
              }}
            >
              <span
                style={{
                  fontSize: 13,
                  color: COLORS.slate[400],
                  fontFamily: FONT.body,
                }}
              >
                {item.label}
              </span>
              <div style={{display: 'flex', alignItems: 'center', gap: 8}}>
                <span
                  style={{
                    fontSize: 14,
                    fontWeight: 600,
                    color: COLORS.white,
                    fontFamily: FONT.mono,
                  }}
                >
                  {item.value}
                </span>
                <span
                  style={{
                    fontSize: 11,
                    color: COLORS.emerald[400],
                    fontFamily: FONT.body,
                  }}
                >
                  {item.trend}
                </span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

// --- Labs Panel ---

const LAB_DATA = [
  {name: 'Cholesterol', value: '196', unit: 'mg/dL', min: 0, max: 300, actual: 196},
  {name: 'TSH', value: '2.1', unit: 'mIU/L', min: 0.5, max: 5.0, actual: 2.1},
  {name: 'Potassium', value: '4.2', unit: 'mEq/L', min: 3.5, max: 5.0, actual: 4.2},
];

const LabsPanel: React.FC<{delay: number}> = ({delay}) => {
  return (
    <div style={{padding: '12px 0'}}>
      <div style={{padding: '0 24px 12px', display: 'flex', alignItems: 'center', gap: 8}}>
        <span
          style={{
            fontSize: 14,
            fontWeight: 600,
            color: COLORS.slate[300],
            fontFamily: FONT.body,
          }}
        >
          Recent Lab Results
        </span>
        <span
          style={{
            fontSize: 12,
            color: COLORS.slate[500],
            fontFamily: FONT.body,
          }}
        >
          Feb 10, 2026
        </span>
      </div>
      {LAB_DATA.map((lab, i) => (
        <LabRow key={lab.name} {...lab} delay={delay + i * 8} />
      ))}
    </div>
  );
};

/**
 * VIDEO 7: Health Record & Connected Services (12 seconds / 360 frames @ 30fps)
 *
 * Shows the Health Dashboard with connected wearable data and comprehensive health record.
 * Sequence:
 * 1. (0-1.5s) Title
 * 2. (1.5-4s) MockBrowser with tab bar, tabs highlight sequentially
 * 3. (4-6s) Vitals tab - heart rate chart + blood pressure
 * 4. (6-8s) Connected tab - Apple Watch + data flow
 * 5. (8-10s) Labs tab - lab results with reference ranges
 * 6. (10-12s) Closing badge
 */
export const HealthRecord: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // --- Determine active tab based on time ---
  const getActiveTab = (): TabName => {
    if (frame < fps * 4) return 'Vitals';
    if (frame < fps * 6) return 'Vitals';
    if (frame < fps * 8) return 'Connected';
    return 'Labs';
  };

  const activeTab = getActiveTab();

  // Tab highlight progress for initial scan
  const tabScanProgress = interpolate(frame, [fps * 1.5, fps * 3.5], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  // Panel transitions
  const vitalsVisible = frame >= fps * 4 && frame < fps * 6;
  const connectedVisible = frame >= fps * 6 && frame < fps * 8;
  const labsVisible = frame >= fps * 8;

  return (
    <AbsoluteFill>
      <Background variant="dark" />

      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 32,
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
              text="Your Complete Health Picture"
              fontSize={52}
              fontWeight={700}
              delay={0}
            />
            <LineReveal
              lines={['All your data in one place']}
              fontSize={22}
              color={COLORS.slate[400]}
              delay={10}
            />
          </div>
        </Sequence>

        {/* MockBrowser with dashboard (1.5s+) */}
        <Sequence from={0} premountFor={Math.round(fps * 1.5)}>
          <MockBrowser delay={Math.round(fps * 1.5)} url="postvisit.ai/health">
            <div
              style={{
                background: COLORS.slate[900],
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
              }}
            >
              {/* Tab bar */}
              <TabBar
                activeTab={activeTab}
                highlightProgress={tabScanProgress}
              />

              {/* Panel content area */}
              <div style={{flex: 1, overflow: 'hidden'}}>
                {/* Vitals content (visible 1.5-6s, then behind) */}
                {(frame < fps * 6 || vitalsVisible) && (
                  <div
                    style={{
                      opacity: frame >= fps * 6 ? 0 : 1,
                    }}
                  >
                    <VitalsPanel delay={Math.round(fps * 4)} />
                  </div>
                )}

                {/* Connected content (6-8s) */}
                {connectedVisible && (
                  <ConnectedPanel delay={Math.round(fps * 6)} />
                )}

                {/* Labs content (8-10s) */}
                {labsVisible && <LabsPanel delay={Math.round(fps * 8)} />}
              </div>
            </div>
          </MockBrowser>
        </Sequence>

        {/* Closing badge (10-12s) */}
        <Sequence from={0} premountFor={Math.round(fps * 10)}>
          <div
            style={{
              display: 'flex',
              gap: 12,
              justifyContent: 'center',
            }}
          >
            <Badge
              text="FDA, NIH, and PubMed verified data sources"
              variant="emerald"
              delay={Math.round(fps * 10)}
            />
          </div>
        </Sequence>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
