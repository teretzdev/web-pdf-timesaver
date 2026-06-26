param(
    [Parameter(Mandatory = $true)]
    [string]$OutputPath,
    [string]$WindowTitleContains = "Clio Draft"
)

Add-Type -AssemblyName System.Drawing

Add-Type @"
using System;
using System.Runtime.InteropServices;
using System.Drawing;
using System.Drawing.Imaging;
public class WinCap {
    [DllImport("user32.dll")] public static extern bool GetWindowRect(IntPtr hWnd, out RECT lpRect);
    [DllImport("user32.dll")] public static extern bool SetForegroundWindow(IntPtr hWnd);
    [DllImport("user32.dll")] public static extern bool IsIconic(IntPtr hWnd);
    [DllImport("user32.dll")] public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
    public struct RECT { public int Left, Top, Right, Bottom; }
    public static void Capture(string titlePart, string path) {
        IntPtr target = IntPtr.Zero;
        foreach (var p in System.Diagnostics.Process.GetProcesses()) {
            if (string.IsNullOrEmpty(p.MainWindowTitle)) continue;
            if (p.MainWindowTitle.IndexOf(titlePart, StringComparison.OrdinalIgnoreCase) >= 0) {
                target = p.MainWindowHandle;
                break;
            }
        }
        if (target == IntPtr.Zero) { throw new Exception("Window not found: " + titlePart); }
        if (IsIconic(target)) ShowWindow(target, 9);
        SetForegroundWindow(target);
        System.Threading.Thread.Sleep(500);
        RECT r; GetWindowRect(target, out r);
        int w = r.Right - r.Left; int h = r.Bottom - r.Top;
        if (w <= 0 || h <= 0) throw new Exception("Invalid window size");
        using (var bmp = new Bitmap(w, h)) {
            using (var g = Graphics.FromImage(bmp)) {
                g.CopyFromScreen(r.Left, r.Top, 0, 0, new Size(w, h));
            }
            var dir = System.IO.Path.GetDirectoryName(path);
            if (!string.IsNullOrEmpty(dir)) System.IO.Directory.CreateDirectory(dir);
            bmp.Save(path, ImageFormat.Png);
        }
    }
}
"@

[WinCap]::Capture($WindowTitleContains, $OutputPath)
Write-Output "Saved: $OutputPath"
