<?php

namespace SuperElf\Points;

class Converter
{

// export class ScoreConverter {
//     protected scoreEnum: ScoreEnum;
//     protected line: FootballLine | undefined;

//     static readonly Points_Win = 1;
//     static readonly Points_Draw = 2;
//     static readonly Goal_Goalkeeper = 4;
//     static readonly Goal_Defender = 8;
//     static readonly Goal_Midfielder = 16;
//     static readonly Goal_Forward = 32;
//     static readonly Assist_Goalkeeper = 64;
//     static readonly Assist_Defender = 128;
//     static readonly Assist_Midfielder = 256;
//     static readonly Assist_Forward = 512;
//     static readonly Goal_Penalty = 1024;
//     static readonly Goal_Own = 2048;
//     static readonly Sheet_Clean_Goalkeeper = 4096;
//     static readonly Sheet_Clean_Defender = 8192;
//     static readonly Sheet_Spotty_Goalkeeper = 16384;
//     static readonly Sheet_Spotty_Defender = 32768;
//     static readonly Card_Yellow = 65536;
//     static readonly Card_Red = 131072;

//     public function __construct(private CompetitionConfig $competitionConfig, private Points $points) {
//
//     }
//
//    public function getScorePoints(Points $points): array {
//
//    }

//     // public getNumber(): number {
//     //     return this.nr;
//     // }

//     private convertToPoints(nr: number): ScoreEnum {
//         switch (nr) {
//             case Score.Points_Win:
//                 return ScoreEnum.WinResult;
//             case Score.Points_Draw:
//                 return ScoreEnum.DrawResult;
//             case Score.Goal_Goalkeeper:
//             case Score.Goal_Defender:
//             case Score.Goal_Midfielder:
//             case Score.Goal_Forward:
//                 return ScoreEnum.Goal;
//             case Score.Assist_Goalkeeper:
//             case Score.Assist_Defender:
//             case Score.Assist_Midfielder:
//             case Score.Assist_Forward:
//                 return ScoreEnum.Goal;

//             case Score.Goal_Penalty:
//                 return ScoreEnum.PenaltyGoal;
//             case Score.Goal_Own:
//                 return ScoreEnum.OwnGoal;
//             case Score.Sheet_Clean_Goalkeeper:
//             case Score.Sheet_Clean_Defender:
//                 return ScoreEnum.CleanSheet;
//             case Score.Sheet_Spotty_Goalkeeper:
//             case Score.Sheet_Spotty_Defender:
//                 return ScoreEnum.SpottySheet;
//             case Score.Card_Yellow:
//                 return ScoreEnum.YellowCard;
//             case Score.Card_Red:
//                 return ScoreEnum.RedCard;
//         }
//         throw new Error('unknown score-number');
//     }

//     private convertToLine(nr: number): FootballLine | undefined {
//         switch (nr) {
//             case Score.Goal_Goalkeeper:
//             case Score.Assist_Goalkeeper:
//             case Score.Sheet_Clean_Goalkeeper:
//             case Score.Sheet_Spotty_Goalkeeper:
//                 return FootballLine.GoalKeeper;
//             case Score.Goal_Defender:
//             case Score.Assist_Defender:
//             case Score.Sheet_Clean_Defender:
//             case Score.Sheet_Spotty_Defender:
//                 return FootballLine.Defense;
//             case Score.Goal_Midfielder:
//             case Score.Assist_Midfielder:
//                 return FootballLine.Midfield;
//             case Score.Goal_Forward:
//             case Score.Assist_Forward:
//                 return FootballLine.Forward;
//         }
//         return undefined;
//     }

//     public getLine(): FootballLine | undefined {
//         return this.line;
//     }
// }
}