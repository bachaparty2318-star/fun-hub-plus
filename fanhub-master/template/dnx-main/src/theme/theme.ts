import { createTheme } from '@mui/material/styles';
import type {} from '@mui/x-data-grid/themeAugmentation';
import Stack from './components/layout/Stack';
import Paper from './components/surface/Paper';
import Link from './components/navigation/Link';
import Drawer from './components/navigation/Drawer';
import Button from './components/button/Button';
import ButtonBase from './components/button/ButtonBase';
import IconButton from './components/button/IconButton';
import Toolbar from './components/button/Toolbar';
import InputBase from './components/input/InputBase';
import FilledInput from './components/input/FilledInput';
import OutlinedInput from './components/input/OutlinedInput';
import InputAdornment from './components/input/InputAdornment';
import FormControlLabel from './components/input/FormControlLabel';
import Checkbox from './components/input/Checkbox';
import Select from './components/input/Select';
import Slider from './components/input/Slider';
import List from './components/list/List';
import ListItemIcon from './components/list/ListItemIcon';
import ListItemText from './components/list/ListItemText';
import ListItemButton from './components/list/ListItemButton';
import Collapse from './components/list/Collapse';
import MenuItem from './components/list/MenuItem';
import Card from './components/card/Card';
import CardMedia from './components/card/CardMedia';
import CardHeader from './components/card/CardHeader';
import CardContent from './components/card/CardContent';
import CardActions from './components/card/CardActions';
import Avatar from './components/data-display/Avatar';
import AvatarGroup from './components/data-display/AvatarGroup';
import Badge from './components/data-display/Badge';
import Chip from './components/data-display/Chip';
import Divider from './components/data-display/Divider';
import DataGrid from './components/data-grid/DataGrid';
import PaginationItem from './components/pagination/PaginationItem';
import CssBaseline from './components/utils/CssBaseline';
import customShadows from './shadows';
import typography from './typography';
import palette from './palette';

export const theme = createTheme({
  typography,
  palette,
  customShadows,
  components: {
    MuiStack: Stack,
    MuiPaper: Paper,
    MuiLink: Link,
    MuiDrawer: Drawer,

    MuiButton: Button,
    MuiButtonBase: ButtonBase,
    MuiIconButton: IconButton,
    MuiToolbar: Toolbar,

    MuiInputBase: InputBase,
    MuiFilledInput: FilledInput,
    MuiOutlinedInput: OutlinedInput,
    MuiInputAdornment: InputAdornment,
    MuiFormControlLabel: FormControlLabel,
    MuiCheckbox: Checkbox,
    MuiSelect: Select,
    MuiSlider: Slider,

    MuiList: List,
    MuiListItemIcon: ListItemIcon,
    MuiListItemText: ListItemText,
    MuiListItemButton: ListItemButton,
    MuiCollapse: Collapse,
    MuiMenuItem: MenuItem,

    MuiCard: Card,
    MuiCardMedia: CardMedia,
    MuiCardHeader: CardHeader,
    MuiCardContent: CardContent,
    MuiCardActions: CardActions,

    MuiAvatar: Avatar,
    MuiAvatarGroup: AvatarGroup,
    MuiBadge: Badge,
    MuiChip: Chip,
    MuiDivider: Divider,

    MuiDataGrid: DataGrid,
    MuiPaginationItem: PaginationItem,
    MuiCssBaseline: CssBaseline,
  },
});
