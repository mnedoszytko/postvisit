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
import {WordReveal} from '../components/AnimatedText';
import {MockBrowser} from '../components/MockPhone';
import {Badge} from '../components/Badge';

/* ------------------------------------------------------------------ */
/*  Data                                                               */
/* ------------------------------------------------------------------ */

const STATS = [
  {label: 'Patients', value: 24, color: COLORS.emerald[400]},
  {label: 'Unread', value: 3, color: COLORS.amber[400]},
  {label: 'Visits', value: 47, color: COLORS.sky[400]},
] as const;

type Severity = 'HIGH' | 'MODERATE' | 'LOW';

interface AlertData {
  title: string;
  patient: string;
  severity: Severity;
  borderColor: string;
  badgeColor: string;
  badgeText: string;
}

const ALERTS: AlertData[] = [
  {
    title: 'Weight gain +3.2kg',
    patient: 'Maria Kowalska',
    severity: 'HIGH',
    borderColor: COLORS.rose[400],
    badgeColor: COLORS.rose[500],
    badgeText: COLORS.rose[400],
  },
  {
    title: 'Elevated BP trend',
    patient: 'Jan Nowak',
    severity: 'MODERATE',
    borderColor: COLORS.amber[400],
    badgeColor: COLORS.amber[500],
    badgeText: COLORS.amber[400],
  },
  {
    title: 'Medication refill due',
    patient: 'Anna Wisniewska',
    severity: 'LOW',
    borderColor: COLORS.sky[400],
    badgeColor: COLORS.sky[500],
    badgeText: COLORS.sky[400],
  },
];

const ACTIONS = [
  'Schedule Follow-up',
  'Renew Prescription',
  'Send Recommendation',
  'Request Labs',
] as const;

/* ------------------------------------------------------------------ */
/*  Sub-components                                                     */
/* ------------------------------------------------------------------ */

/** Animated counter that ticks from 0 to target */
const AnimatedCounter: React.FC<{
  target: number;
  label: string;
  color: string;
  delay: number;
}> = ({target, label, color, delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const progress = spring({frame, fps, delay, config: {damping: 30, stiffness: 80}});
  const value = Math.round(interpolate(progress, [0, 1], [0, target]));
  const cardAppear = spring({frame, fps, delay: delay - 5, config: {damping: 200}});

  return (
    <div
      style={{
        flex: 1,
        background: `${COLORS.slate[800]}cc`,
        border: `1px solid ${COLORS.slate[700]}`,
        borderRadius: 10,
        padding: '16px 20px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 4,
        opacity: cardAppear,
        transform: `translateY(${interpolate(cardAppear, [0, 1], [12, 0])}px)`,
      }}
    >
      <span style={{fontSize: 32, fontWeight: 700, color, fontFamily: FONT.mono}}>
        {value}
      </span>
      <span style={{fontSize: 13, color: COLORS.slate[400], fontWeight: 500}}>
        {label}
      </span>
    </div>
  );
};

/** Alert card with severity badge */
const AlertCard: React.FC<{
  alert: AlertData;
  delay: number;
}> = ({alert, delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const slideX = interpolate(appear, [0, 1], [-40, 0]);

  return (
    <div
      style={{
        background: `${COLORS.slate[800]}dd`,
        borderRadius: 10,
        padding: '14px 18px',
        borderLeft: `4px solid ${alert.borderColor}`,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        opacity: appear,
        transform: `translateX(${slideX}px)`,
      }}
    >
      <div style={{display: 'flex', flexDirection: 'column', gap: 4}}>
        <span style={{fontSize: 15, fontWeight: 600, color: COLORS.white}}>
          {alert.title}
        </span>
        <span style={{fontSize: 12, color: COLORS.slate[400]}}>
          {alert.patient}
        </span>
      </div>
      <div
        style={{
          padding: '4px 10px',
          borderRadius: 6,
          background: `${alert.badgeColor}22`,
          border: `1px solid ${alert.badgeColor}44`,
        }}
      >
        <span style={{fontSize: 11, fontWeight: 600, color: alert.badgeText}}>
          {alert.severity}
        </span>
      </div>
    </div>
  );
};

/** Quick action button */
const ActionButton: React.FC<{
  label: string;
  delay: number;
}> = ({label, delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 14, stiffness: 120}});
  const scale = interpolate(appear, [0, 1], [0.85, 1]);

  return (
    <div
      style={{
        background: `linear-gradient(135deg, ${COLORS.emerald[600]}44, ${COLORS.emerald[500]}22)`,
        border: `1px solid ${COLORS.emerald[500]}44`,
        borderRadius: 8,
        padding: '12px 16px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        opacity: appear,
        transform: `scale(${scale})`,
      }}
    >
      <span style={{fontSize: 12, fontWeight: 600, color: COLORS.emerald[300]}}>
        {label}
      </span>
    </div>
  );
};

/* ------------------------------------------------------------------ */
/*  Main Composition                                                   */
/* ------------------------------------------------------------------ */

/**
 * VIDEO 9: Doctor Dashboard - Clinical Intelligence (12s / 360 frames @ 30fps)
 *
 * Shows the doctor's view with patient monitoring, alerts, quick actions,
 * and AI chat audit trail.
 */
export const DoctorDashboard: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // --- Title fade (0-1.5s) ---
  const titleDelay = Math.round(fps * 0.2);

  // --- Browser appear (1.5s) ---
  const browserDelay = Math.round(fps * 1.5);

  // --- Stats counters (2-3.5s) ---
  const statsDelay = Math.round(fps * 2);

  // --- Alerts (4-7s) ---
  const alertsDelay = Math.round(fps * 4);
  const alertsHeaderAppear = spring({
    frame,
    fps,
    delay: alertsDelay - 5,
    config: {damping: 200},
  });

  // --- Quick Actions panel (7-9s) ---
  const actionsDelay = Math.round(fps * 7);
  const actionsAppear = spring({
    frame,
    fps,
    delay: actionsDelay,
    config: {damping: 200},
  });
  const actionsSlideX = interpolate(actionsAppear, [0, 1], [60, 0]);

  // --- Audit section (9-10.5s) ---
  const auditDelay = Math.round(fps * 9);
  const auditAppear = spring({frame, fps, delay: auditDelay, config: {damping: 200}});

  // --- Bottom badge (10.5-12s) ---
  const badgeDelay = Math.round(fps * 10.5);

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
        }}
      >
        {/* Title */}
        <Sequence from={0} premountFor={titleDelay}>
          <div
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: 8,
              position: 'absolute',
              top: 40,
              left: 0,
              right: 0,
            }}
          >
            <WordReveal text="Doctor Dashboard" fontSize={44} delay={titleDelay} />
            <div style={{marginTop: 4}}>
              <WordReveal
                text="Clinical intelligence at your fingertips"
                fontSize={20}
                color={COLORS.slate[400]}
                fontWeight={400}
                delay={titleDelay + 8}
              />
            </div>
          </div>
        </Sequence>

        {/* Browser with dashboard content */}
        <div style={{marginTop: 60}}>
          <MockBrowser delay={browserDelay} url="postvisit.ai/doctor">
            <div
              style={{
                padding: 24,
                display: 'flex',
                flexDirection: 'column',
                gap: 16,
                height: '100%',
              }}
            >
              {/* Dashboard header */}
              <Sequence from={0} premountFor={browserDelay + 5}>
                <DashboardHeader delay={browserDelay + 5} />
              </Sequence>

              {/* Stats row */}
              <div style={{display: 'flex', gap: 12}}>
                {STATS.map((stat, i) => (
                  <AnimatedCounter
                    key={stat.label}
                    target={stat.value}
                    label={stat.label}
                    color={stat.color}
                    delay={statsDelay + i * 6}
                  />
                ))}
              </div>

              {/* Main content area: alerts left, actions right */}
              <div style={{display: 'flex', gap: 16, flex: 1}}>
                {/* Alerts section */}
                <div style={{flex: 1, display: 'flex', flexDirection: 'column', gap: 10}}>
                  <div
                    style={{
                      opacity: alertsHeaderAppear,
                      display: 'flex',
                      alignItems: 'center',
                      gap: 8,
                    }}
                  >
                    <div
                      style={{
                        width: 8,
                        height: 8,
                        borderRadius: '50%',
                        background: COLORS.rose[400],
                      }}
                    />
                    <span
                      style={{
                        fontSize: 15,
                        fontWeight: 600,
                        color: COLORS.slate[200],
                      }}
                    >
                      Active Alerts
                    </span>
                  </div>

                  {ALERTS.map((alert, i) => (
                    <AlertCard
                      key={alert.patient}
                      alert={alert}
                      delay={alertsDelay + i * 10}
                    />
                  ))}
                </div>

                {/* Quick Actions panel */}
                <div
                  style={{
                    width: 280,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 12,
                    opacity: actionsAppear,
                    transform: `translateX(${actionsSlideX}px)`,
                  }}
                >
                  <span
                    style={{
                      fontSize: 15,
                      fontWeight: 600,
                      color: COLORS.slate[200],
                    }}
                  >
                    Quick Actions
                  </span>
                  <div
                    style={{
                      display: 'grid',
                      gridTemplateColumns: '1fr 1fr',
                      gap: 8,
                    }}
                  >
                    {ACTIONS.map((action, i) => (
                      <ActionButton
                        key={action}
                        label={action}
                        delay={actionsDelay + 5 + i * 5}
                      />
                    ))}
                  </div>

                  {/* AI Chat Audit section */}
                  <div
                    style={{
                      marginTop: 12,
                      background: `${COLORS.slate[800]}cc`,
                      border: `1px solid ${COLORS.slate[700]}`,
                      borderRadius: 10,
                      padding: '14px 16px',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: 8,
                      opacity: auditAppear,
                      transform: `translateY(${interpolate(auditAppear, [0, 1], [10, 0])}px)`,
                    }}
                  >
                    <div style={{display: 'flex', alignItems: 'center', gap: 8}}>
                      <div
                        style={{
                          width: 6,
                          height: 6,
                          borderRadius: '50%',
                          background: COLORS.emerald[400],
                        }}
                      />
                      <span
                        style={{
                          fontSize: 13,
                          fontWeight: 600,
                          color: COLORS.slate[200],
                        }}
                      >
                        AI Chat Audit
                      </span>
                    </div>
                    <span
                      style={{
                        fontSize: 11,
                        color: COLORS.slate[400],
                        lineHeight: 1.5,
                      }}
                    >
                      Review all AI conversations with patients. Full transcript and
                      reasoning trace available.
                    </span>
                    <div style={{display: 'flex', gap: 6}}>
                      {['3 new', '12 reviewed'].map((item, i) => (
                        <div
                          key={item}
                          style={{
                            padding: '3px 8px',
                            borderRadius: 4,
                            background: i === 0
                              ? `${COLORS.emerald[500]}22`
                              : `${COLORS.slate[700]}`,
                            border: `1px solid ${i === 0 ? COLORS.emerald[500] + '44' : COLORS.slate[600]}`,
                          }}
                        >
                          <span
                            style={{
                              fontSize: 10,
                              fontWeight: 500,
                              color: i === 0 ? COLORS.emerald[300] : COLORS.slate[400],
                            }}
                          >
                            {item}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </MockBrowser>
        </div>

        {/* Bottom badge */}
        <Sequence from={0} premountFor={badgeDelay}>
          <div
            style={{
              position: 'absolute',
              bottom: 36,
              left: 0,
              right: 0,
              display: 'flex',
              justifyContent: 'center',
            }}
          >
            <Badge
              text="HIPAA-compliant audit trail on every interaction"
              variant="emerald"
              delay={badgeDelay}
            />
          </div>
        </Sequence>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};

/** Dashboard header bar */
const DashboardHeader: React.FC<{delay: number}> = ({delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        opacity: appear,
        paddingBottom: 12,
        borderBottom: `1px solid ${COLORS.slate[700]}`,
      }}
    >
      <div style={{display: 'flex', alignItems: 'center', gap: 12}}>
        {/* Doctor avatar */}
        <div
          style={{
            width: 40,
            height: 40,
            borderRadius: '50%',
            background: `linear-gradient(135deg, ${COLORS.emerald[500]}, ${COLORS.emerald[700]})`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <span style={{fontSize: 18, fontWeight: 700, color: COLORS.white}}>SC</span>
        </div>
        <div style={{display: 'flex', flexDirection: 'column'}}>
          <span
            style={{fontSize: 16, fontWeight: 600, color: COLORS.white}}
          >
            Dr. Sarah Chen
          </span>
          <span style={{fontSize: 12, color: COLORS.slate[400]}}>
            Cardiology
          </span>
        </div>
      </div>

      <div style={{display: 'flex', alignItems: 'center', gap: 8}}>
        <div
          style={{
            padding: '4px 10px',
            borderRadius: 6,
            background: `${COLORS.emerald[500]}22`,
            border: `1px solid ${COLORS.emerald[500]}44`,
          }}
        >
          <span style={{fontSize: 11, fontWeight: 500, color: COLORS.emerald[300]}}>
            Online
          </span>
        </div>
      </div>
    </div>
  );
};
