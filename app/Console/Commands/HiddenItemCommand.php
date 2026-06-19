<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HiddenItemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hidden-item:solve {--up=} {--right=} {--down=}';

    protected $description = 'Solve the hidden item game by finding probable coordinate points';

    public function handle()
    {
        $grid = $this->getGrid();
        $start = $this->findStartingPosition($grid);

        if (!$start) {
            $this->error("Starting position X not found in the grid.");
            return Command::FAILURE;
        }

        $up = $this->option('up');
        $right = $this->option('right');
        $down = $this->option('down');

        // Check if we are running in exact movement mode (all three options provided)
        if ($up !== null && $right !== null && $down !== null) {
            $upVal = (int)$up;
            $rightVal = (int)$right;
            $downVal = (int)$down;

            $this->line("Movement used: Up {$upVal}, Right {$rightVal}, Down {$downVal}");

            $solutions = $this->solve($grid, $start, $upVal, $rightVal, $downVal);

            if (empty($solutions)) {
                $this->line("No probable item locations found for the given movement.");
            } else {
                $this->info("Probable item locations:");
                foreach ($solutions as $sol) {
                    $row = $sol[0] + 1;
                    $col = $sol[1] + 1;
                    $this->line("- Row {$row}, Col {$col}");
                }
            }
        } else {
            // Mode A: Auto solve
            $this->line("Original grid:");
            foreach ($grid as $row) {
                $this->line($row);
            }
            $this->line("");

            $startRow = $start[0] + 1;
            $startCol = $start[1] + 1;
            $this->line("Starting position: Row {$startRow}, Col {$startCol}");
            $this->line("");

            $solutions = $this->solve($grid, $start);

            if (empty($solutions)) {
                $this->line("No probable item locations found.");
            } else {
                $this->line("Probable item locations:");
                // Sort solutions by row then column for neat output
                usort($solutions, function ($a, $b) {
                    if ($a[0] === $b[0]) {
                        return $a[1] <=> $b[1];
                    }
                    return $a[0] <=> $b[0];
                });

                foreach ($solutions as $sol) {
                    $row = $sol[0] + 1;
                    $col = $sol[1] + 1;
                    $this->line("- Row {$row}, Col {$col}");
                }
                $this->line("");

                $this->line("Grid with probable item locations:");
                $rendered = $this->renderGridWithProbableLocations($grid, $solutions);
                foreach ($rendered as $row) {
                    $this->line($row);
                }
            }
        }

        return Command::SUCCESS;
    }

    private function getGrid()
    {
        return [
            '########',
            '#......#',
            '#.###..#',
            '#...#.##',
            '#X#....#',
            '########',
        ];
    }

    private function findStartingPosition($grid)
    {
        foreach ($grid as $r => $row) {
            $c = strpos($row, 'X');
            if ($c !== false) {
                return [$r, $c];
            }
        }
        return null;
    }

    private function isWalkable($grid, $r, $c)
    {
        if ($r < 0 || $r >= count($grid)) {
            return false;
        }
        $row = $grid[$r];
        if ($c < 0 || $c >= strlen($row)) {
            return false;
        }
        return $row[$c] !== '#';
    }

    private function move($grid, $start, $up, $right, $down)
    {
        $r = $start[0];
        $c = $start[1];

        // 1. Move Up
        for ($i = 0; $i < $up; $i++) {
            $r--;
            if (!$this->isWalkable($grid, $r, $c)) {
                return null;
            }
        }

        // 2. Move Right
        for ($i = 0; $i < $right; $i++) {
            $c++;
            if (!$this->isWalkable($grid, $r, $c)) {
                return null;
            }
        }

        // 3. Move Down
        for ($i = 0; $i < $down; $i++) {
            $r++;
            if (!$this->isWalkable($grid, $r, $c)) {
                return null;
            }
        }

        // Posisi akhir tidak boleh obstacle #, tidak boleh X, dan harus berada di clear path .
        if ($r === $start[0] && $c === $start[1]) {
            return null;
        }

        if ($grid[$r][$c] !== '.') {
            return null;
        }

        return [$r, $c];
    }

    private function solve($grid, $start, $exactUp = null, $exactRight = null, $exactDown = null)
    {
        $solutions = [];

        $maxUp = count($grid);
        $maxRight = strlen($grid[0]);
        $maxDown = count($grid);

        if ($exactUp !== null && $exactRight !== null && $exactDown !== null) {
            $res = $this->move($grid, $start, $exactUp, $exactRight, $exactDown);
            if ($res !== null) {
                $solutions[] = $res;
            }
        } else {
            // Auto solve - try all valid steps combinations
            for ($u = 1; $u < $maxUp; $u++) {
                for ($r = 1; $r < $maxRight; $r++) {
                    for ($d = 1; $d < $maxDown; $d++) {
                        $res = $this->move($grid, $start, $u, $r, $d);
                        if ($res !== null) {
                            $key = $res[0] . ',' . $res[1];
                            $solutions[$key] = $res;
                        }
                    }
                }
            }
        }

        return array_values($solutions);
    }

    private function renderGridWithProbableLocations($grid, $locations)
    {
        $locMap = [];
        foreach ($locations as $loc) {
            $locMap[$loc[0]][$loc[1]] = true;
        }

        $renderedGrid = [];
        foreach ($grid as $r => $row) {
            $newRow = '';
            for ($c = 0; $c < strlen($row); $c++) {
                if (isset($locMap[$r][$c])) {
                    $newRow .= '$';
                } else {
                    $newRow .= $row[$c];
                }
            }
            $renderedGrid[] = $newRow;
        }

        return $renderedGrid;
    }
}
