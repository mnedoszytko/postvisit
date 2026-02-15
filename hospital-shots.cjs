const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
        Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
        ShadingType, PageNumber, PageBreak } = require('docx');
const fs = require('fs');

const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const cellMargins = { top: 80, bottom: 80, left: 120, right: 120 };

const CONTENT_WIDTH = 9360;

function headerCell(text, width) {
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: { fill: "1B2A4A", type: ShadingType.CLEAR },
    margins: cellMargins,
    verticalAlign: "center",
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: "FFFFFF", font: "Arial", size: 20 })] })]
  });
}

function cell(text, width, opts = {}) {
  const runs = [];
  if (opts.bold) {
    runs.push(new TextRun({ text, bold: true, font: "Arial", size: 20 }));
  } else {
    // Parse bold markers **text**
    const parts = text.split(/(\*\*[^*]+\*\*)/);
    parts.forEach(p => {
      if (p.startsWith('**') && p.endsWith('**')) {
        runs.push(new TextRun({ text: p.slice(2, -2), bold: true, font: "Arial", size: 20 }));
      } else {
        runs.push(new TextRun({ text: p, font: "Arial", size: 20 }));
      }
    });
  }
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: opts.shading ? { fill: opts.shading, type: ShadingType.CLEAR } : undefined,
    margins: cellMargins,
    children: [new Paragraph({ children: runs })]
  });
}

function shotTable(shots) {
  const colWidths = [600, 2200, 3760, 1400, 1400];
  const rows = [
    new TableRow({
      children: [
        headerCell("#", colWidths[0]),
        headerCell("SHOT", colWidths[1]),
        headerCell("WHAT TO SAY / DO", colWidths[2]),
        headerCell("LOCATION", colWidths[3]),
        headerCell("DURATION", colWidths[4]),
      ]
    })
  ];

  shots.forEach((s, i) => {
    const bg = s.priority === "MUST" ? "FFF3E0" : (i % 2 === 0 ? "F5F7FA" : undefined);
    rows.push(new TableRow({
      children: [
        cell(s.num, colWidths[0], { shading: bg, bold: true }),
        cell(s.name, colWidths[1], { shading: bg, bold: true }),
        cell(s.action, colWidths[2], { shading: bg }),
        cell(s.location, colWidths[3], { shading: bg }),
        cell(s.duration, colWidths[4], { shading: bg }),
      ]
    }));
  });

  return new Table({
    width: { size: CONTENT_WIDTH, type: WidthType.DXA },
    columnWidths: colWidths,
    rows
  });
}

function sectionTitle(text) {
  return new Paragraph({
    spacing: { before: 300, after: 150 },
    children: [new TextRun({ text, bold: true, font: "Arial", size: 28, color: "1B2A4A" })]
  });
}

function note(text) {
  return new Paragraph({
    spacing: { before: 100, after: 100 },
    indent: { left: 200 },
    children: [new TextRun({ text, font: "Arial", size: 18, italics: true, color: "666666" })]
  });
}

function bodyText(text) {
  return new Paragraph({
    spacing: { before: 80, after: 80 },
    children: [new TextRun({ text, font: "Arial", size: 20 })]
  });
}

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 20 } } }
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1080, right: 1440, bottom: 1080, left: 1440 }
      }
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          alignment: AlignmentType.RIGHT,
          children: [new TextRun({ text: "PostVisit.ai | Hospital Shoot | Feb 13, 2026", font: "Arial", size: 16, color: "999999" })]
        })]
      })
    },
    children: [
      // TITLE
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 100 },
        children: [new TextRun({ text: "HOSPITAL SHOOTING GUIDE", bold: true, font: "Arial", size: 40, color: "1B2A4A" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 80 },
        children: [new TextRun({ text: "PostVisit.ai Demo Video | 1 Hour Session", font: "Arial", size: 24, color: "666666" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 300 },
        children: [
          new TextRun({ text: "Priority: ", font: "Arial", size: 20, color: "666666" }),
          new TextRun({ text: "MUST", bold: true, font: "Arial", size: 20, color: "D84315" }),
          new TextRun({ text: " = without this the video doesn\u2019t work  |  ", font: "Arial", size: 20, color: "666666" }),
          new TextRun({ text: "NICE", bold: true, font: "Arial", size: 20, color: "1565C0" }),
          new TextRun({ text: " = adds production value", font: "Arial", size: 20, color: "666666" }),
        ]
      }),

      // BEFORE YOU START
      sectionTitle("\u26A0\uFE0F  BEFORE YOU START (5 min)"),
      bodyText("\u2022  Phone: landscape mode, 4K 30fps (Settings \u2192 Camera \u2192 Record Video)"),
      bodyText("\u2022  Clean the lens (seriously)"),
      bodyText("\u2022  Remove badge / name tag with real name if visible"),
      bodyText("\u2022  White coat on, stethoscope visible"),
      bodyText("\u2022  Airplane mode OFF (but silence notifications)"),
      bodyText("\u2022  Find a colleague or nurse willing to walk with you for 5 seconds (Shot 8)"),
      note("All dialogue in ENGLISH. Speak slowly and clearly \u2014 subtitles will be burned in."),

      new Paragraph({ children: [] }),

      // PART 1
      sectionTitle("PART 1 \u2014 CREDIBILITY & STORY (30 min)"),
      note("These shots establish you as a real cardiologist. This is what separates your demo from 499 others."),
      new Paragraph({ children: [] }),

      shotTable([
        {
          num: "1",
          name: "CUTLAB ENTRANCE",
          action: "Walk into the Cutlab. Camera follows you. Say: **\u201CThis is the Cutlab \u2014 this is where we cure heart attacks.\u201D** Show room, monitors, equipment. 2-3 sec pan across the room.",
          location: "Cutlab door",
          duration: "8-10s",
          priority: "MUST"
        },
        {
          num: "2",
          name: "CUTLAB WORK",
          action: "B-roll: you doing something at the console / looking at monitors / adjusting equipment. **No dialogue needed.** Camera can be handheld, slightly behind you.",
          location: "Cutlab",
          duration: "5-8s",
          priority: "MUST"
        },
        {
          num: "3",
          name: "ECHO MACHINE",
          action: "Sit next to the Echo. Look at camera. Say: **\u201CI\u2019m Michal, interventional cardiologist from Brussels. By day I fix hearts. But code has always been my happy place.\u201D**",
          location: "Echo room",
          duration: "10-12s",
          priority: "MUST"
        },
        {
          num: "4",
          name: "THE PROBLEM",
          action: "At your desk, patient files / EHR behind you. Say: **\u201CEvery day, patients leave my office \u2014 and I know they\u2019ll forget most of what I just said. The discharge papers are confusing. The medical jargon doesn\u2019t help. They deserve something better.\u201D**",
          location: "Office / desk",
          duration: "12-15s",
          priority: "MUST"
        },
        {
          num: "5",
          name: "REVERSE SCRIBE",
          action: "Standing, gesturing. Say: **\u201CDoctors now have AI scribes. But what about the patient? PostVisit flips this. The patient records the visit, and our AI translates doctor-speak into human language. That\u2019s the reverse scribe.\u201D**",
          location: "Corridor / office",
          duration: "12-15s",
          priority: "MUST"
        },
        {
          num: "6",
          name: "PHONE ON DESK",
          action: "Place iPhone on desk, screen up. Tap to start \u201Crecording\u201D (can be fake UI or just Voice Memos). Hold 3 seconds. This is the visual anchor for \u201Cmy phone was on the table during the visit.\u201D",
          location: "Desk / consultation",
          duration: "3-5s",
          priority: "MUST"
        },
        {
          num: "7",
          name: "EKG / MONITOR",
          action: "B-roll closeup: EKG strip on monitor showing PVCs, or any cardiac rhythm. If no live patient \u2014 show a printed EKG strip or textbook. **No dialogue.**",
          location: "Any monitor / printout",
          duration: "3-4s",
          priority: "NICE"
        },
        {
          num: "8",
          name: "CORRIDOR WALK",
          action: "Walk down hospital corridor with a colleague. Chat naturally (inaudible \u2014 this is B-roll). Camera follows from behind or side. Shows teamwork, real hospital environment.",
          location: "Hospital corridor",
          duration: "4-5s",
          priority: "NICE"
        },
        {
          num: "9",
          name: "CORRIDOR SOLO",
          action: "Walk toward camera. Say: **\u201COver the years I\u2019ve built drug indexes, electronic health records, clinical tools. But this hackathon finally gave me the chance to build what I\u2019ve wanted for my patients for a long time.\u201D**",
          location: "Hospital corridor",
          duration: "10-12s",
          priority: "MUST"
        },
      ]),

      new Paragraph({ children: [new PageBreak()] }),

      // PART 2
      sectionTitle("PART 2 \u2014 PRODUCT NARRATION (20 min)"),
      note("Record these as voiceover pieces. You can be walking, sitting, or standing \u2014 but hospital background adds credibility. These will play OVER screen recordings in the final edit."),
      new Paragraph({ children: [] }),

      shotTable([
        {
          num: "10",
          name: "PRODUCT INTRO",
          action: "To camera: **\u201CPostVisit gives every patient a personal health space. Your visit summary, your medications, your vitals, your medical record \u2014 all in one place. And you can talk to it.\u201D**",
          location: "Office / corridor",
          duration: "10-12s",
          priority: "MUST"
        },
        {
          num: "11",
          name: "OPUS 4.6 MENTION",
          action: "To camera: **\u201CUnder the hood, this runs on Claude Opus 4.6 with a million-token context window. That means the AI sees your entire visit \u2014 the transcript, the guidelines, your health record \u2014 all at once. No context is ever lost.\u201D**",
          location: "Anywhere",
          duration: "12-15s",
          priority: "MUST"
        },
        {
          num: "12",
          name: "CONTEXT EXPLANATION",
          action: "To camera: **\u201CThis isn\u2019t a generic health chatbot. It\u2019s anchored in YOUR visit, YOUR medications, YOUR test results. Every answer comes from what your doctor actually said and did.\u201D**",
          location: "Anywhere",
          duration: "10-12s",
          priority: "MUST"
        },
        {
          num: "13",
          name: "DOCTOR LOOP",
          action: "To camera: **\u201CAnd the doctor stays in the loop. I can see what my patient asked, what the AI explained, and get alerted if something needs my attention.\u201D**",
          location: "At desk / office",
          duration: "8-10s",
          priority: "MUST"
        },
        {
          num: "14",
          name: "CLOSING HOOK",
          action: "To camera, warm tone: **\u201CI built this because my patients deserve better than a confusing piece of paper after one of the most stressful moments of their lives.\u201D** Pause. Small smile.",
          location: "Anywhere meaningful",
          duration: "8-10s",
          priority: "MUST"
        },
      ]),

      new Paragraph({ children: [] }),

      // PART 3
      sectionTitle("PART 3 \u2014 B-ROLL EXTRAS (5 min)"),
      note("Quick shots, no dialogue. Grab these fast at the end. Pure visual texture for editing."),
      new Paragraph({ children: [] }),

      shotTable([
        {
          num: "15",
          name: "HANDS ON KEYBOARD",
          action: "Closeup of your hands typing code on laptop. Hospital desk or Cutlab console visible in background.",
          location: "Any desk",
          duration: "3-4s",
          priority: "NICE"
        },
        {
          num: "16",
          name: "SCRUBS / COAT",
          action: "Putting on white coat or checking stethoscope. Quick, cinematic.",
          location: "Changing room / office",
          duration: "2-3s",
          priority: "NICE"
        },
        {
          num: "17",
          name: "HOSPITAL EXTERIOR",
          action: "Wide shot of hospital building entrance. Establishes location.",
          location: "Outside hospital",
          duration: "3-4s",
          priority: "NICE"
        },
        {
          num: "18",
          name: "PATIENT WAITING",
          action: "Empty waiting room or corridor with chairs. Evokes \u201Cthe patient experience.\u201D No people needed.",
          location: "Waiting area",
          duration: "2-3s",
          priority: "NICE"
        },
      ]),

      new Paragraph({ children: [new PageBreak()] }),

      // TIMELINE
      sectionTitle("SUGGESTED ORDER (1 hour)"),
      bodyText("Shoot in order of location, not video order \u2014 saves walking time:"),
      new Paragraph({ children: [] }),

      new Table({
        width: { size: CONTENT_WIDTH, type: WidthType.DXA },
        columnWidths: [1500, 2500, 2680, 2680],
        rows: [
          new TableRow({ children: [
            headerCell("TIME", 1500), headerCell("LOCATION", 2500),
            headerCell("SHOTS", 2680), headerCell("NOTES", 2680)
          ]}),
          new TableRow({ children: [
            cell("0:00-0:15", 1500, { shading: "F5F7FA" }),
            cell("Cutlab", 2500, { shading: "F5F7FA" }),
            cell("#1, #2", 2680, { shading: "F5F7FA" }),
            cell("Entrance + B-roll", 2680, { shading: "F5F7FA" })
          ]}),
          new TableRow({ children: [
            cell("0:15-0:25", 1500), cell("Echo room", 2500),
            cell("#3", 2680), cell("Intro to camera", 2680)
          ]}),
          new TableRow({ children: [
            cell("0:25-0:40", 1500, { shading: "F5F7FA" }),
            cell("Office / desk", 2500, { shading: "F5F7FA" }),
            cell("#4, #6, #13, #15", 2680, { shading: "F5F7FA" }),
            cell("Problem + phone + doctor loop + hands", 2680, { shading: "F5F7FA" })
          ]}),
          new TableRow({ children: [
            cell("0:40-0:50", 1500), cell("Corridor", 2500),
            cell("#5, #8, #9", 2680), cell("Reverse scribe + walk + years of building", 2680)
          ]}),
          new TableRow({ children: [
            cell("0:50-0:58", 1500, { shading: "F5F7FA" }),
            cell("Anywhere quiet", 2500, { shading: "F5F7FA" }),
            cell("#10, #11, #12, #14", 2680, { shading: "F5F7FA" }),
            cell("Product narration (voiceover pieces)", 2680, { shading: "F5F7FA" })
          ]}),
          new TableRow({ children: [
            cell("0:58-1:00", 1500), cell("Various", 2500),
            cell("#7, #16, #17, #18", 2680), cell("B-roll extras", 2680)
          ]}),
        ]
      }),

      new Paragraph({ spacing: { before: 300 }, children: [] }),

      // TIPS
      sectionTitle("QUICK TIPS"),
      bodyText("\u2022  Record each shot 2-3 times \u2014 you\u2019ll want options in editing"),
      bodyText("\u2022  Landscape (horizontal) ONLY \u2014 final video is 16:9"),
      bodyText("\u2022  Look at the camera lens, not the screen"),
      bodyText("\u2022  Pause 2 seconds before and after speaking (easier to cut)"),
      bodyText("\u2022  If you stumble, just pause and restart the sentence \u2014 don\u2019t stop recording"),
      bodyText("\u2022  Natural pace > rushed delivery. You have 3 minutes, not 30 seconds"),
      bodyText("\u2022  MUST shots = 9 shots, ~85 seconds of dialogue. The rest fills in editing"),

      new Paragraph({ spacing: { before: 200 }, children: [] }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "Good luck. Make it count. \uD83C\uDFA5", font: "Arial", size: 22, color: "999999", italics: true })]
      }),
    ]
  }]
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync("/sessions/peaceful-happy-babbage/mnt/postvisit/hospital-shots.docx", buffer);
  console.log("Done: hospital-shots.docx");
});
