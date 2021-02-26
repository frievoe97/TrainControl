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

%% Geschwindigkeit über Zeit

figure(1);
fname = '../json/speedOverTime.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val_1 = jsondecode(str);

speedOverTime_x = val_1(:,1)-1612811700;

speedOverTime_y = val_1(:,2);


p = plot(speedOverTime_x,speedOverTime_y);

p.LineWidth = 2;
fontSize = 18;
title("Geschwindigkeit in Abhähngigkeit der Zeit", 'FontSize', fontSize);
xlabel("Zeit [s]", 'FontSize', fontSize);
ylabel("Geschwindigkeit [km/h]", 'FontSize', fontSize);
x0=10;
y0=10;
width=1100;
height=600;
axis([min(speedOverTime_x)*0.9 max(speedOverTime_x)*1.1 0 max(speedOverTime_y)+10]);
set(gcf,'position',[x0,y0,width,height]);
set(gca, 'FontSize', 14);
t = gca;
exportgraphics(t,'SpeedOverTime.jpg','Resolution',300);

%% Geschwindigkeit über Position

% vmaxOverPosition_Position vmaxOverPosition_v_max

figure(2);
fname = '../json/speedOverPosition.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverPosition_x = val(:,1);

speedOverPosition_y = val(:,2);


p = plot(speedOverPosition_x,speedOverPosition_y);

fontSize = 18;
title("Geschwindigkeit in Abhähngigkeit der Position", 'FontSize', fontSize);
xlabel("Strecke [m]", 'FontSize', fontSize);
ylabel("Geschwindigkeit [km/h]", 'FontSize', fontSize);
x0=10;

line([vmaxOverPosition_Position(1) vmaxOverPosition_Position(1)], [0 vmaxOverPosition_v_max(1)],'LineWidth',1,'color','black'); 
line([vmaxOverPosition_Position(1) vmaxOverPosition_Position(1 + 1)], [vmaxOverPosition_v_max(1) vmaxOverPosition_v_max(1)],'LineWidth',1,'color','black');

for i = 2:size(vmaxOverPosition_Position) - 1
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i + 1)],'LineWidth',1,'color','black'); 
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i)],'LineWidth',1,'color','black'); 
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i + 1)], [vmaxOverPosition_v_max(i) vmaxOverPosition_v_max(i)],'LineWidth',1,'color','black');
end

line([vmaxOverPosition_Position(end) vmaxOverPosition_Position(end)], [0 vmaxOverPosition_v_max(end - 1)],'LineWidth',1,'color','black'); 
%line([vmaxOverPosition_Position(end - 1) vmaxOverPosition_Position(end)], [vmaxOverPosition_v_max(end) vmaxOverPosition_v_max(end)],'LineWidth',1,'color','black');

p.LineWidth = 2;

y0=10;
width=1100;
height=600;
axis([min(speedOverPosition_x)-10 max(speedOverPosition_x)+10 0 max(vmaxOverPosition_v_max)+10]);
set(gcf,'position',[x0,y0,width,height]);
set(gca, 'FontSize', 14);
t = gca;
exportgraphics(t,'SpeedOverPosition.jpg','Resolution',300);


%% Geschwindigkeit über Position (ver 2)

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

line([vmaxOverPosition_Position(1) vmaxOverPosition_Position(1)], [0 vmaxOverPosition_v_max(1)],'LineWidth',1,'color','black'); 
line([vmaxOverPosition_Position(1) vmaxOverPosition_Position(1 + 1)], [vmaxOverPosition_v_max(1) vmaxOverPosition_v_max(1)],'LineWidth',1,'color','black');

for i = 2:size(vmaxOverPosition_Position) - 1
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i + 1)],'LineWidth',1,'color','black'); 
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i)], [0 vmaxOverPosition_v_max(i)],'LineWidth',1,'color','black'); 
   line([vmaxOverPosition_Position(i) vmaxOverPosition_Position(i + 1)], [vmaxOverPosition_v_max(i) vmaxOverPosition_v_max(i)],'LineWidth',1,'color','black');
end

line([vmaxOverPosition_Position(end) vmaxOverPosition_Position(end)], [0 vmaxOverPosition_v_max(end - 1)],'LineWidth',1,'color','black'); 
%line([vmaxOverPosition_Position(end - 1) vmaxOverPosition_Position(end)], [vmaxOverPosition_v_max(end) vmaxOverPosition_v_max(end)],'LineWidth',1,'color','black');

p.LineWidth = 2;

y0=10;
width=1100;
height=600;
axis([min(speedOverPosition_x)-10 max(speedOverPosition_x)+10 0 max(vmaxOverPosition_v_max)+10]);
set(gcf,'position',[x0,y0,width,height]);
set(gca, 'FontSize', 14);
t = gca;
exportgraphics(t,'SpeedOverPosition.jpg','Resolution',300);







