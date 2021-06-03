%% Cumulative Sections NEW

fname = '../json/VMaxOverCumulativeSections.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
vmaxOverPosition = jsondecode(str);

vmaxOverPosition_Position = vmaxOverPosition(:,1);

vmaxOverPosition_v_max = vmaxOverPosition(:,2);

%% Cumulative Sections

fname = '../json/cumulativeSections.json'; 
fid = fopen(fname); 
raw = fread(fid,inf);
str = char(raw'); 
fclose(fid); 
cum = jsondecode(str);


%% Geschwindigkeit über Position

% vmaxOverPosition_Position vmaxOverPosition_v_max

figure(3);
fname = '../json/speedOverPosition_v1.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverPosition_x_v1 = val(:,1);
speedOverPosition_y_v1 = val(:,2);

p = plot(speedOverPosition_x_v1,speedOverPosition_y_v1);

fontSize = 18;
title("Geschwindigkeit in Abhähngigkeit der Position", 'FontSize', fontSize);
xlabel("Strecke [m]", 'FontSize', fontSize);
ylabel("Geschwindigkeit [km/h]", 'FontSize', fontSize);
x0=10;

line([0 0], [0 vmaxOverPosition_v_max(1)],'LineWidth',1,'color','black'); 
line([0 vmaxOverPosition_Position(1)], [vmaxOverPosition_v_max(1) vmaxOverPosition_v_max(1)],'LineWidth',1,'color','black');

for i = 1:size(vmaxOverPosition_Position) - 1
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i + 1)], [vmaxOverPosition_v_max(i + 1) vmaxOverPosition_v_max(i + 1)],'LineWidth',1,'color','black');
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i + 1)],'LineWidth',1,'color','black'); 
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i)],'LineWidth',1,'color','black'); 
   line([vmaxOverPosition_Position(i + 1) vmaxOverPosition_Position(i + 1)], [0 vmaxOverPosition_v_max(i + 1)],'LineWidth',1,'color','black'); 
end

p.LineWidth = 2;

y0=10;
width=1100;
height=600;
axis([-30 max(vmaxOverPosition_Position)+30 0 max(vmaxOverPosition_v_max)+10]);
set(gcf,'position',[x0,y0,width,height]);
set(gca, 'FontSize', 14);
t = gca;
exportgraphics(t,'SpeedOverPosition.jpg','Resolution',300);