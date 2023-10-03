import { useTheme } from '@/components/providers/theme-provider';
import { Button } from '@/components/ui/button';
import { MoonIcon, SunIcon } from '@radix-ui/react-icons';

export function ModeToggle() {
  const { theme, setTheme } = useTheme();
  const handleToggleMode = () => {
    setTheme(theme === 'light' ? 'dark' : 'light');
  };

  return (
    <Button variant='outline' size='icon' onClick={handleToggleMode}>
      <MoonIcon className='h-[1.2rem] w-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0' />
      <SunIcon className='absolute h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100' />
      <span className='sr-only'>Toggle theme</span>
    </Button>
  );
}
