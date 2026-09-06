const { app, BrowserWindow } = require('electron');
const path = require('node:path');

const appUrl = process.env.OMNIPOS_URL || 'http://127.0.0.1:8001';
let mainWindow;

function isAllowedNavigation(target) {
    try {
        const expected = new URL(appUrl);
        const candidate = new URL(target);
        return candidate.origin === expected.origin;
    } catch {
        return false;
    }
}

function createWindow() {
    mainWindow = new BrowserWindow({
        title: 'OmniPOS Modular SaaS',
        width: 1440,
        height: 900,
        minWidth: 1024,
        minHeight: 680,
        backgroundColor: '#f8f9ff',
        webPreferences: {
            preload: path.join(__dirname, 'preload.cjs'),
            contextIsolation: true,
            nodeIntegration: false,
            sandbox: true,
        },
    });

    mainWindow.webContents.on('will-navigate', (event, target) => {
        if (!isAllowedNavigation(target)) event.preventDefault();
    });
    mainWindow.webContents.setWindowOpenHandler(() => ({ action: 'deny' }));
    mainWindow.webContents.on('did-finish-load', () => mainWindow.setTitle('OmniPOS Modular SaaS'));
    mainWindow.loadURL(appUrl);
}

app.whenReady().then(() => {
    createWindow();
    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});
