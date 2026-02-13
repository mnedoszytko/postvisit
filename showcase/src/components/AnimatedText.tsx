import React from 'react';
import {useCurrentFrame, useVideoConfig, spring, interpolate} from 'remotion';
import {COLORS} from '../theme';

/** Typewriter text reveal */
export const TypewriterText: React.FC<{
  text: string;
  fontSize?: number;
  color?: string;
  delay?: number;
  speed?: number;
}> = ({text, fontSize = 32, color = COLORS.white, delay = 0, speed = 1.5}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const charsToShow = Math.floor(
    interpolate(frame - delay, [0, text.length / speed], [0, text.length], {
      extrapolateLeft: 'clamp',
      extrapolateRight: 'clamp',
    }),
  );

  const cursorOpacity = frame % (fps / 2) < fps / 4 ? 1 : 0;

  return (
    <span style={{fontSize, color, fontWeight: 400, lineHeight: 1.5}}>
      {text.slice(0, charsToShow)}
      {charsToShow < text.length && (
        <span style={{opacity: cursorOpacity, color: COLORS.emerald[400]}}>|</span>
      )}
    </span>
  );
};

/** Word-by-word fade in */
export const WordReveal: React.FC<{
  text: string;
  fontSize?: number;
  color?: string;
  fontWeight?: number;
  delay?: number;
  stagger?: number;
}> = ({text, fontSize = 48, color = COLORS.white, fontWeight = 700, delay = 0, stagger = 3}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const words = text.split(' ');

  return (
    <div style={{display: 'flex', flexWrap: 'wrap', gap: fontSize * 0.25}}>
      {words.map((word, i) => {
        const wordSpring = spring({
          frame,
          fps,
          delay: delay + i * stagger,
          config: {damping: 200},
        });

        return (
          <span
            key={i}
            style={{
              fontSize,
              fontWeight,
              color,
              opacity: wordSpring,
              transform: `translateY(${interpolate(wordSpring, [0, 1], [20, 0])}px)`,
              display: 'inline-block',
            }}
          >
            {word}
          </span>
        );
      })}
    </div>
  );
};

/** Staggered line reveal */
export const LineReveal: React.FC<{
  lines: string[];
  fontSize?: number;
  color?: string;
  delay?: number;
  stagger?: number;
}> = ({lines, fontSize = 24, color = COLORS.slate[300], delay = 0, stagger = 8}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  return (
    <div style={{display: 'flex', flexDirection: 'column', gap: fontSize * 0.6}}>
      {lines.map((line, i) => {
        const lineSpring = spring({
          frame,
          fps,
          delay: delay + i * stagger,
          config: {damping: 200},
        });

        return (
          <div
            key={i}
            style={{
              fontSize,
              color,
              opacity: lineSpring,
              transform: `translateX(${interpolate(lineSpring, [0, 1], [-30, 0])}px)`,
            }}
          >
            {line}
          </div>
        );
      })}
    </div>
  );
};
