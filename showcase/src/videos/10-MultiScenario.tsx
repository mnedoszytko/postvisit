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
import {Badge} from '../components/Badge';

/* ------------------------------------------------------------------ */
/*  Data                                                               */
/* ------------------------------------------------------------------ */

type SpecialtyVariant = 'rose' | 'amber' | 'violet' | 'sky';

interface Specialty {
  name: string;
  variant: SpecialtyVariant;
  color: string;
}

const SPECIALTIES: Specialty[] = [
  {name: 'Cardiology', variant: 'rose', color: COLORS.rose[400]},
  {name: 'Endocrinology', variant: 'amber', color: COLORS.amber[400]},
  {name: 'Gastroenterology', variant: 'violet', color: COLORS.violet[400]},
  {name: 'Pulmonology', variant: 'sky', color: COLORS.sky[400]},
];

interface ScenarioData {
  name: string;
  condition: string;
  specialty: SpecialtyVariant;
}

const SCENARIOS: ScenarioData[] = [
  // Row 1
  {name: 'M. Kowalska', condition: 'PVCs & Palpitations', specialty: 'rose'},
  {name: 'J. Nowak', condition: 'Coronary Stenosis', specialty: 'rose'},
  {name: 'A. Lewandowski', condition: 'Gastric Bypass Pre-op', specialty: 'violet'},
  {name: 'K. Mazur', condition: 'Hypertension Follow-up', specialty: 'rose'},
  // Row 2
  {name: 'P. Kaminski', condition: 'Chest Pain & Carotid', specialty: 'rose'},
  {name: 'E. Wojcik', condition: 'Fibromyalgia', specialty: 'rose'},
  {name: 'T. Kaczmarek', condition: 'Aortic Aneurysm', specialty: 'rose'},
  {name: 'D. Zielinski', condition: 'Pre-op Stent', specialty: 'rose'},
  // Row 3
  {name: 'R. Szymanski', condition: 'BP Monitoring', specialty: 'rose'},
  {name: 'I. Wozniak', condition: 'Diabetes Management', specialty: 'amber'},
  {name: 'H. Dabrowski', condition: "Crohn's Flare", specialty: 'violet'},
  {name: 'B. Kozlowski', condition: 'COPD Exacerbation', specialty: 'sky'},
];

const VARIANT_COLORS: Record<SpecialtyVariant, {bg: string; border: string; text: string; avatar: string}> = {
  rose: {
    bg: COLORS.rose[500] + '22',
    border: COLORS.rose[500] + '44',
    text: COLORS.rose[400],
    avatar: `linear-gradient(135deg, ${COLORS.rose[400]}, ${COLORS.rose[500]})`,
  },
  amber: {
    bg: COLORS.amber[500] + '22',
    border: COLORS.amber[500] + '44',
    text: COLORS.amber[400],
    avatar: `linear-gradient(135deg, ${COLORS.amber[400]}, ${COLORS.amber[500]})`,
  },
  violet: {
    bg: COLORS.violet[500] + '22',
    border: COLORS.violet[500] + '44',
    text: COLORS.violet[400],
    avatar: `linear-gradient(135deg, ${COLORS.violet[400]}, ${COLORS.violet[500]})`,
  },
  sky: {
    bg: COLORS.sky[500] + '22',
    border: COLORS.sky[500] + '44',
    text: COLORS.sky[400],
    avatar: `linear-gradient(135deg, ${COLORS.sky[400]}, ${COLORS.sky[500]})`,
  },
};

/* ------------------------------------------------------------------ */
/*  Sub-components                                                     */
/* ------------------------------------------------------------------ */

/** Specialty badge with spring animation */
const SpecialtyBadge: React.FC<{
  specialty: Specialty;
  delay: number;
}> = ({specialty, delay}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 14, stiffness: 120}});
  const scale = interpolate(appear, [0, 1], [0.7, 1]);
  const colors = VARIANT_COLORS[specialty.variant];

  return (
    <div
      style={{
        padding: '10px 24px',
        borderRadius: 24,
        background: colors.bg,
        border: `1px solid ${colors.border}`,
        opacity: appear,
        transform: `scale(${scale})`,
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
          background: specialty.color,
        }}
      />
      <span style={{fontSize: 16, fontWeight: 600, color: colors.text}}>
        {specialty.name}
      </span>
    </div>
  );
};

/** Scenario card showing patient info */
const ScenarioCard: React.FC<{
  scenario: ScenarioData;
  index: number;
  delay: number;
  isHighlighted: boolean;
}> = ({scenario, index, delay, isHighlighted}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const appear = spring({frame, fps, delay, config: {damping: 200}});
  const cardScale = interpolate(appear, [0, 1], [0.85, 1]);
  const colors = VARIANT_COLORS[scenario.specialty];

  // Highlight effect for first card (7.5-8.5s)
  const highlightDelay = Math.round(fps * 7.5);
  const highlightProgress = isHighlighted
    ? spring({frame, fps, delay: highlightDelay, config: {damping: 14, stiffness: 100}})
    : 0;
  const highlightScale = interpolate(highlightProgress, [0, 1], [1, 1.08]);
  const highlightGlow = interpolate(highlightProgress, [0, 1], [0, 0.5]);

  // Initials from name
  const initials = scenario.name
    .split(' ')
    .map((n) => n[0])
    .join('');

  return (
    <div
      style={{
        background: `${COLORS.slate[800]}cc`,
        border: `1px solid ${isHighlighted && highlightProgress > 0.1
          ? colors.border
          : COLORS.slate[700]}`,
        borderRadius: 10,
        padding: '14px 12px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 8,
        opacity: appear,
        transform: `scale(${cardScale * highlightScale})`,
        boxShadow: isHighlighted
          ? `0 0 ${30 * highlightGlow}px ${colors.text}${Math.round(highlightGlow * 80).toString(16).padStart(2, '0')}`
          : 'none',
      }}
    >
      {/* Avatar circle */}
      <div
        style={{
          width: 36,
          height: 36,
          borderRadius: '50%',
          background: colors.avatar,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <span style={{fontSize: 12, fontWeight: 700, color: COLORS.white}}>
          {initials}
        </span>
      </div>

      {/* Patient name */}
      <span
        style={{
          fontSize: 11,
          fontWeight: 600,
          color: COLORS.white,
          textAlign: 'center',
          lineHeight: 1.2,
        }}
      >
        {scenario.name}
      </span>

      {/* Specialty micro-badge */}
      <div
        style={{
          padding: '2px 8px',
          borderRadius: 8,
          background: colors.bg,
          border: `1px solid ${colors.border}`,
        }}
      >
        <span style={{fontSize: 9, fontWeight: 500, color: colors.text}}>
          {SPECIALTIES.find((s) => s.variant === scenario.specialty)?.name ?? ''}
        </span>
      </div>

      {/* Condition */}
      <span
        style={{
          fontSize: 10,
          color: COLORS.slate[400],
          textAlign: 'center',
          lineHeight: 1.3,
        }}
      >
        {scenario.condition}
      </span>
    </div>
  );
};

/* ------------------------------------------------------------------ */
/*  Main Composition                                                   */
/* ------------------------------------------------------------------ */

/**
 * VIDEO 10: 12 Clinical Scenarios Across 4 Specialties (10s / 300 frames @ 30fps)
 *
 * Showcases the breadth of the demo system with specialty badges
 * and a 4x3 grid of scenario cards.
 */
export const MultiScenario: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // --- Title (0-1.5s) ---
  const titleDelay = Math.round(fps * 0.2);

  // --- Specialty badges (1.5-3s) ---
  const badgesDelay = Math.round(fps * 1.5);

  // --- Grid cards (3-7.5s) ---
  const gridDelay = Math.round(fps * 3);

  // --- Bottom text (8.5-10s) ---
  const textDelay = Math.round(fps * 8.5);
  const textAppear = spring({frame, fps, delay: textDelay, config: {damping: 200}});

  // --- Bottom badges (9-10s) ---
  const bottomBadgeDelay = Math.round(fps * 9.2);

  return (
    <AbsoluteFill>
      <Background variant="radial" />

      <AbsoluteFill
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          padding: '40px 80px',
          gap: 24,
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
            }}
          >
            <WordReveal text="12 Clinical Scenarios" fontSize={44} delay={titleDelay} />
            <div style={{marginTop: 2}}>
              <WordReveal
                text="Realistic demo data across specialties"
                fontSize={20}
                color={COLORS.slate[400]}
                fontWeight={400}
                delay={titleDelay + 8}
              />
            </div>
          </div>
        </Sequence>

        {/* Specialty badges row */}
        <Sequence from={0} premountFor={badgesDelay}>
          <div
            style={{
              display: 'flex',
              gap: 16,
              justifyContent: 'center',
              marginTop: 4,
            }}
          >
            {SPECIALTIES.map((spec, i) => (
              <SpecialtyBadge
                key={spec.name}
                specialty={spec}
                delay={badgesDelay + i * 5}
              />
            ))}
          </div>
        </Sequence>

        {/* 4x3 Grid of scenario cards */}
        <Sequence from={0} premountFor={gridDelay}>
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(4, 1fr)',
              gap: 14,
              width: '100%',
              maxWidth: 1100,
              marginTop: 8,
            }}
          >
            {SCENARIOS.map((scenario, i) => {
              // Stagger: left-to-right, top-to-bottom
              const row = Math.floor(i / 4);
              const col = i % 4;
              const stagger = row * 6 + col * 3;

              return (
                <ScenarioCard
                  key={scenario.name}
                  scenario={scenario}
                  index={i}
                  delay={gridDelay + stagger}
                  isHighlighted={i === 0}
                />
              );
            })}
          </div>
        </Sequence>

        {/* Bottom descriptive text */}
        <Sequence from={0} premountFor={textDelay}>
          <div
            style={{
              opacity: textAppear,
              transform: `translateY(${interpolate(textAppear, [0, 1], [12, 0])}px)`,
              textAlign: 'center',
              maxWidth: 800,
              marginTop: 8,
            }}
          >
            <span
              style={{
                fontSize: 18,
                fontWeight: 400,
                color: COLORS.slate[300],
                fontFamily: FONT.body,
                lineHeight: 1.6,
              }}
            >
              Each with full dialogue, SOAP notes, medical terms, and patient profiles
            </span>
          </div>
        </Sequence>

        {/* Bottom badges */}
        <Sequence from={0} premountFor={bottomBadgeDelay}>
          <div
            style={{
              display: 'flex',
              gap: 12,
              justifyContent: 'center',
            }}
          >
            <Badge
              text="AI-generated patient photos"
              variant="emerald"
              delay={bottomBadgeDelay}
            />
            <Badge
              text="Realistic clinical dialogues"
              variant="slate"
              delay={bottomBadgeDelay + 5}
            />
          </div>
        </Sequence>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
