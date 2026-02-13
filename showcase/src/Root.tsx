import React from 'react';
import {Composition, Folder} from 'remotion';
import {VIDEO} from './theme';

import {HeroIntro} from './videos/01-HeroIntro';
import {ReverseScribe} from './videos/02-ReverseScribe';
import {AiProcessing} from './videos/03-AiProcessing';
import {VisitSummary} from './videos/04-VisitSummary';
import {ContextualChat} from './videos/05-ContextualChat';
import {DeepReasoning} from './videos/06-DeepReasoning';
import {HealthRecord} from './videos/07-HealthRecord';
import {MedicalLibrary} from './videos/08-MedicalLibrary';
import {DoctorDashboard} from './videos/09-DoctorDashboard';
import {MultiScenario} from './videos/10-MultiScenario';

const {width, height, fps} = VIDEO;

export const RemotionRoot: React.FC = () => {
  return (
    <Folder name="PostVisit-Showcase">
      <Composition
        id="01-HeroIntro"
        component={HeroIntro}
        durationInFrames={10 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="02-ReverseScribe"
        component={ReverseScribe}
        durationInFrames={12 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="03-AiProcessing"
        component={AiProcessing}
        durationInFrames={10 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="04-VisitSummary"
        component={VisitSummary}
        durationInFrames={12 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="05-ContextualChat"
        component={ContextualChat}
        durationInFrames={15 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="06-DeepReasoning"
        component={DeepReasoning}
        durationInFrames={12 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="07-HealthRecord"
        component={HealthRecord}
        durationInFrames={12 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="08-MedicalLibrary"
        component={MedicalLibrary}
        durationInFrames={12 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="09-DoctorDashboard"
        component={DoctorDashboard}
        durationInFrames={12 * fps}
        fps={fps}
        width={width}
        height={height}
      />
      <Composition
        id="10-MultiScenario"
        component={MultiScenario}
        durationInFrames={10 * fps}
        fps={fps}
        width={width}
        height={height}
      />
    </Folder>
  );
};
