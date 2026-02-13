import {execSync} from 'child_process';

const compositions = [
  '01-HeroIntro',
  '02-ReverseScribe',
  '03-AiProcessing',
  '04-VisitSummary',
  '05-ContextualChat',
  '06-DeepReasoning',
  '07-HealthRecord',
  '08-MedicalLibrary',
  '09-DoctorDashboard',
  '10-MultiScenario',
];

console.log(`Rendering ${compositions.length} compositions...\n`);

for (const id of compositions) {
  console.log(`Rendering ${id}...`);
  try {
    execSync(
      `npx remotion render src/index.ts ${id} out/${id}.mp4 --codec h264`,
      {stdio: 'inherit'},
    );
    console.log(`  Done: out/${id}.mp4\n`);
  } catch (e) {
    console.error(`  Failed: ${id}\n`);
  }
}

console.log('All done!');
