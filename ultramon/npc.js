// npc.js - Battle NPCs definition
const npc = [
    {
        id: "pelle",
        name: "Rival Pelle",
        route: 1,
        x: 14,
        y: 8,
        color: "#3498db",
        img: "npcpng/pelle.png",
        dialogue: "Let's see how much stronger you've gotten since last time!",
        postBattleDialogue: "How is this possible!? Alright, theres one more test for you! See ya at the end of the cave futher! I have heard that a cosmic leader is surrounding in the cave!",
        team: [
            { id: "sproupup", lvl: 5 }
        ],
        rewards: { coins: 1000 },
        defeated: false
    },
    {
        id: "challenger_mia",
        name: "Challenger Mia",
        route: 1,
        x: 22,
        y: 15,
        color: "#9b59b6",
        img: "npcpng/trainer1.png",
        dialogue: "My cute partners will easily overwhelm you!",
        postBattleDialogue: "Wow, you're incredible! I need to train a lot more.",
        team: [
            { id: "samupillar", lvl: 5 },
            { id: "coalapling", lvl: 4 }
        ],
        rewards: { coins: 500 },
        defeated: false
    },
    {
        id: "expert_leo",
        name: "Route Expert Leo",
        route: 2,
        x: 14,
        y: 16,
        color: "#e67e22",
        img: "npcpng/trainer2.png",
        dialogue: "I've trained across many routes. Think you can handle my team?",
        postBattleDialogue: "Oh, i think you should become the new route expert!",
        team: [
            { id: "flaragon", lvl: 6 },
            { id: "samupod", lvl: 6 },
            { id: "hydrini", lvl: 6 }
        ],
        rewards: { coins: 300 },
        defeated: false
    },
    {
        id: "team_cosmic_1",
        name: "Team Cosmic Trainer Amir",
        route: 3,
        x: 14,
        y: 16,
        color: "#e67e22",
        img: "npcpng/trainer2.png",
        dialogue: "Team Cosmic's plans are absolute. Stay out of our way!",
        postBattleDialogue: "My boss will make you into pancakes!.",
        team: [
            { id: "samupod", lvl: 8 },
            { id: "turnace", lvl: 8 },
            { id: "bladee", lvl: 10 }
        ],
        rewards: { coins: 1500 },
        defeated: false
    },
    {
        id: "Thirsty Trainer",
        name: "Thirsty Trainer",
        route: 4,
        x: 3,
        y: 25,
        color: "#e67e22",
        img: "npcpng/trainer2.png",
        dialogue: "Whew... trekking through here makes me parched, but I won't lose a battle!",
        postBattleDialogue: "When is it the end of here? I can not be here anymore!",
        team: [
            { id: "malime", lvl: 10 },
            { id: "hydrini", lvl: 8 },
            { id: "sparkwing", lvl: 10 }
        ],
        rewards: { coins: 500 },
        defeated: false
    },
    {
        id: "miner",
        name: "Cave Miner",
        route: 4,
        x: 15,
        y: 23,
        color: "#e67e22",
        img: "npcpng/trainer3.png",
        dialogue: "I've been unearthing rare gems down here for hours. Time to strike gold!",
        postBattleDialogue: "You should help me to find gems instead!.",
        team: [
            { id: "malime", lvl: 10 },
            { id: "malime", lvl: 10 },
            { id: "magmud", lvl: 25 }
        ],
        rewards: { coins: 500 },
        defeated: false
    },
    {
        id: "cosmic1",
        name: "Cosmic Leader Milo",
        route: 6,
        x: 15,
        y: 12,
        color: "#e67e22",
        img: "npcpng/milo.png",
        dialogue: "Witness the supreme power of the cosmos! Your journey ends here.",
        postBattleDialogue: "Incredible, teenager! We will meet again.",
        team: [
            { id: "bouldercap", lvl: 16 },
            { id: "malime", lvl: 16 },
            { id: "bladee", lvl: 16 },
            { id: "turnace", lvl: 16 },
            { id: "venefish", lvl: 16 },
            { id: "cosmo", lvl: 16 }
        ],
        rewards: { coins: 500 },
        defeated: false
    }
];
